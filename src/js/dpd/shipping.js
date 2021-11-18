if (!window.DPD) {
    window.DPD = {};
}

DPD.Connect = Class.create({
    initialize: function (container, config) {
        if (container === "DPD_window_content") {
            this.iframe = window.parent.document.getElementById('DPD_window_content');
            innerDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
            this.container = innerDoc.getElementById('parcelshop');
        }
        else {
            this.container = this.getDefaultContainer();
        }

        var shippingMethod = jQuery('[name="shipping_method"]:checked');

        if(shippingMethod.val() !== 'dpdparcelshops_dpdparcelshops' && container !== "DPD_window_content") {
            this.hideParcelshops();
        }

        this.config = config;

        this.showParcelsLinkClick = this.displayParcelsInline.bind(this);
        this.invalidateParcelLinkClick = this.invalidateParcel.bind(this);
        this.toggleExtraInfo = this.toggleExtraInfo.bind(this);
        this.showExtraInfo = this.showExtraInfo.bind(this);
        this.hideExtraInfo = this.hideExtraInfo.bind(this);
        this.saveShipping = this.updateProgressBlock.bind(this);

        this.bindEvents();

        if(!window.jwtToken && !window.parent.jwtToken) {
            var options = {
                method: 'GET',
                url: this.config.jwtUrl
            };

            jQuery.ajax(options).done(function (json) {
                var data = JSON.parse(json);

                window.jwtToken = data.token;

                this.hideInitialLoader();

                this.loadElements(container);
            }.bind(this));
        } else {
            this.hideInitialLoader();
            this.loadElements(container);
        }
    },
    hideInitialLoader: function () {
        jQuery('.initial-loader').hide();
        jQuery('#showparcels').show();
    },
    loadElements: function (container) {
        if (this.config.dpdSelected && !this.container.down('.parcelshop-selected') && container !== "DPD_window_content") {
            var openingHoursJson = sessionStorage.getItem('parcelshop');

            if(openingHoursJson) {
                this.saveParcelShop(JSON.parse(openingHoursJson));
                return;
            }
        }

        var shippingMethod = jQuery('[name="shipping_method"]:checked');

        if (
            (
                shippingMethod.val() === 'dpdparcelshops_dpdparcelshops' &&
                this.container.down('#dpd-connect-map-container')
            ) ||
            container === "DPD_window_content"
        ) {
            this.showParcelShops();
            this.showMap();
        }
    },
    bindEvents: function () {
        jQuery('[name="shipping_method"]').change(function(event){
            if(
                event.target.value === 'dpdparcelshops_dpdparcelshops'
            ) {
                jQuery('#dpd_parcelshop_container').show();
                this.showParcelShops();

                if(this.container.down('#dpd-connect-map-container')) {
                    this.showMap();
                }
            } else {
                this.hideParcelshops();
            }
        }.bind(this));

        if(window.top === window && this.config.gmapsDisplay) {
            // Attach the event listener for communication with the iframe
            window.onmessage = (event) => {
                if (!event.data) {
                    return;
                }

                switch(event.data.action) {
                    case 'saveParcelshop':
                        this.saveParcelShop(event.data.data);
                        break;
                }
            }
        }

        this.showParcelsLink = this.container.down('#showparcels');
        if (this.showParcelsLink) {
            this.showParcelsLink.observe('click', this.showParcelsLinkClick);
        }

        this.shippingRadioButtons = $('checkout-shipping-method-load');
        if (this.shippingRadioButtons) {
            this.shippingRadioButtons.observe('change', this.saveShipping);
        }

        this.showInfo = this.container.down('.extrainfo');
        if (this.showInfo) {
            this.showInfo.observe('click', this.toggleExtraInfo);
            this.showInfo.observe('mouseover', this.showExtraInfo);
            this.showInfo.observe('mouseout', this.hideExtraInfo);

            document.observe('click', function (event) {
                if(!event.target.classList.contains('extrainfo')) {
                    this.hideExtraInfo(event);
                }
            }.bind(this));
        }

        this.invalidateParcelsLink = this.container.down('.invalidateParcel');
        if (this.invalidateParcelsLink) {
            this.invalidateParcelsLink.observe('click', this.invalidateParcelLinkClick);
        }
    },
    displayParcelsInline: function () {
        this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        var dialog = this.container.down('.dialog');
        if (this.config.gmapsDisplay && !dialog) {
            showDPDWindow(this.config.windowParcelUrl + "?windowed=true",
                'iframe',
                (parseInt(this.config.gmapsWidth.replace("px", ""))), (parseInt(this.config.gmapsHeight.replace("px", ""))),
                this.config
            );
        }
        else {
            this.parcelselectLink = this.container.down('#showparcels');
            if (this.parcelselectLink) {
                this.parcelselectLink.replace('<div class="dpdloaderwrapper"><span class="dpdloader"></span>' + this.config.loadingmessage + '</div>' +
                    '<input type="hidden" class="DPD-confirmed" value="0"/>');

                var reloadurl = this.config.invalidateParcelUrl;

                new parent.Ajax.Updater({success: this.container.down('#dpd')}, reloadurl, {
                    type: "GET",
                    asynchronous: true,
                    evalScripts: true
                })
            }
        }
    },
    showMap: function() {
        DPDConnect.onParcelshopSelected = this.saveParcelShop.bind(this);

        if (this.config.gmapsDpdParcelShopUseDpdKey) {
            DPDConnect.show(window.jwtToken || window.parent.jwtToken, this.config.shippingaddress, this.config.localCode);
        } else if(!this.config.gmapsDpdParcelShopGoogleKey) {
            console.error('No gmaps api key provided');
        } else {
            DPDConnect.show(window.jwtToken || window.parent.jwtToken, this.config.shippingaddress, this.config.localCode, this.config.gmapsDpdParcelShopGoogleKey);
        }
    },
    showParcelShops: function () {
        jQuery('#parcelshop').show();
    },
    hideParcelshops: function () {
        jQuery('#parcelshop').hide();
    },
    hideMap: function () {
        jQuery('#dpd-connect-map-container').hide();
    },
    saveParcelShopFromIframe: function (evt) {
        window.top.postMessage({action: 'saveParcelshop', data: evt}, '*');
    },
    saveParcelShop: function (evt) {
        this.hideMap();

        // Save the openinghours to sessionStorage
        sessionStorage.setItem('parcelshop', JSON.stringify(evt));

        if (this.container.id == "parcelshop") {
            parent.Windows.close("DPD_window");
            this.saveParcelShopFromIframe(evt);
            return;
        }

        //this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        var reloadurl = this.config.saveParcelUrl;
        var parcelshop = this.container.down('#parcelshop');

        parcelshop.update('<div class="dpdloaderwrapper" style="margin-bottom:35px;"><span class="dpdloader"></span><span class="message"></span></div><input type="hidden" class="DPD-confirmed" value="0"/>');

        var options = {
            method: 'POST',
            url: reloadurl,
            data: evt
        };

        jQuery.ajax(options).done(function (data) {
            this.container.down('#dpd').update(data);

            var price = this.container.down('#custom-shipping-amount').value;
            var priceContainer = this.container.down('label[for="s_method_dpdparcelshops_dpdparcelshops"] span');
            var oldPrice = priceContainer.innerHTML;
            priceContainer.update(price);
            if(price.substring(1) != oldPrice.substring(1)) {
                priceContainer.addClassName('price-changed');
                parent.setTimeout(function(){
                    priceContainer.removeClassName('price-changed');
                }.bind(this), 2000)
            }
        }.bind(this));
    },
    invalidateParcel: function (evt) {
        var reloadurl = this.config.invalidateParcelUrl;
        var parcelshop = this.container.down('#parcelshop');
        var dialog = this.container.down('.dialog');
        this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        if (this.config.gmapsDisplay && !dialog) {
            this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
            showDPDWindow(this.config.windowParcelUrl + "?windowed=true",
                'iframe',
                (parseInt(this.config.gmapsWidth.replace("px", ""))), (parseInt(this.config.gmapsHeight.replace("px", ""))),
                this.config
            );
        } else {
            parcelshop.update('<div class="dpdloaderwrapper"><span class="dpdloader"></span>' + this.config.loadingmessage + '</div>' +
                '<input type="hidden" class="DPD-confirmed" value="0"/>');
            new parent.Ajax.Updater({success: this.container.down('#dpd')}, reloadurl, {
                type: "GET",
                asynchronous: true,
                evalScripts: true
            })
        }
    },
    showExtraInfo: function (evt) {
        var left = evt.target.offsetLeft;
        this.container.down('.extrainfowrapper').show().setStyle({left: left + 'px'});
    },
    hideExtraInfo: function (evt) {
        if(this.container.down('.extrainfowrapper')) {
            this.container.down('.extrainfowrapper').hide();
        }
    },
    toggleExtraInfo: function (evt) {
        if (this.container.down('.extrainfowrapper').visible()) {
            this.hideExtraInfo(evt);
        }
        else {
            this.showExtraInfo(evt);
        }
    },
    updateProgressBlock: function () {
        var progressContents = $$('#checkout-progress-wrapper a[href="#shipping_method"]')[0];
        if (!progressContents) {
            progressContents = $$('.opc-block-progress a[href="#shipping_method"]')[0];
        }

        if (progressContents != undefined) {
            if (!$('s_method_dpdparcelshops_dpdparcelshops').checked && progressContents.up().next().innerHTML) {
                var request = new Ajax.Request(
                    shipping.saveUrl,
                    {
                        method: 'post',
                        onSuccess: checkout.reloadProgressBlock(),
                        parameters: Form.serialize(shipping.form)
                    }
                );
            }
        }
    },
    getDefaultContainer: function () {
        return document.getElementById('checkout-shipping-method-load');
    }
});
