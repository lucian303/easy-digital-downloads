!function(e){var t={};function n(a){if(t[a])return t[a].exports;var r=t[a]={i:a,l:!1,exports:{}};return e[a].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,a){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:a})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var a=Object.create(null);if(n.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(a,r,function(t){return e[t]}.bind(null,r));return a},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=227)}({1:function(e,t){e.exports=jQuery},227:function(e,t,n){(function(e){var t={isMounted:!1,init:function(){document.getElementById("edd-paypal-container")&&this.initButtons("#edd-paypal-container","checkout"),e(document.body).on("edd_discount_applied",this.maybeRefreshPage),e(document.body).on("edd_discount_removed",this.maybeRefreshPage)},isPayPal:function(){var t=!1;return e("select#edd-gateway, input.edd-gateway").length&&(t=e("meta[name='edd-chosen-gateway']").attr("content")),!t&&edd_scripts.default_gateway&&(t=edd_scripts.default_gateway),"paypal_commerce"===t},maybeRefreshPage:function(e,n){(0===n.total_plain&&t.isPayPal()||!t.isMounted&&t.isPayPal()&&n.total_plain>0)&&window.location.reload()},setErrorHtml:function(t,n,a){if("checkout"===n&&"undefined"!=typeof edd_global_vars&&edd_global_vars.checkout_error_anchor)(r=document.getElementById("edd-paypal-errors-wrap"))&&(r.innerHTML=a);else if("buy_now"===n){var r,o=t.closest(".edd_download_purchase_form");(r=!!o&&o.querySelector(".edd-paypal-checkout-buy-now-error-wrapper"))&&(r.innerHTML=a)}e(document.body).trigger("edd_checkout_error",[a])},initButtons:function(e,n){t.isMounted=!0,paypal.Buttons(t.getButtonArgs(e,n)).render(e),document.dispatchEvent(new CustomEvent("edd_paypal_buttons_mounted"))},getButtonArgs:function(n,a){var r="checkout"===a?document.getElementById("edd_purchase_form"):n.closest(".edd_download_purchase_form"),o="checkout"===a?r.querySelector("#edd-paypal-errors-wrap"):r.querySelector(".edd-paypal-checkout-buy-now-error-wrapper"),d="checkout"===a?document.getElementById("edd-paypal-spinner"):r.querySelector(".edd-paypal-spinner"),c=r.querySelector('input[name="edd_process_paypal_nonce"]'),i=r.querySelector('input[name="edd-process-paypal-token"]'),u="subscription"===eddPayPalVars.intent?"createSubscription":"createOrder",l=r.querySelectorAll("[required]"),s={onInit:function(e,t){t.disable(),r.checkValidity()&&t.enable(),l.forEach((function(e){e.addEventListener("change",(function(e){r.checkValidity()?t.enable():t.disable()}))}))},onClick:function(e,t){if(!r.reportValidity())return!1},onApprove:function(e,r){var o=new FormData;return o.append("action",eddPayPalVars.approvalAction),o.append("edd_process_paypal_nonce",c.value),o.append("token",i.getAttribute("data-token")),o.append("timestamp",i.getAttribute("data-timestamp")),e.orderID&&o.append("paypal_order_id",e.orderID),e.subscriptionID&&o.append("paypal_subscription_id",e.subscriptionID),fetch(edd_scripts.ajaxurl,{method:"POST",body:o}).then((function(e){return e.json()})).then((function(e){if(e.success&&e.data.redirect_url)window.location=e.data.redirect_url;else{d.style.display="none";var o=e.data.message?e.data.message:eddPayPalVars.defaultError;if(t.setErrorHtml(n,a,o),e.data.retry)return r.restart()}}))},onError:function(e){d.style.display="none",e.name="",t.setErrorHtml(n,a,e)},onCancel:function(e){d.style.display="none";var t=new FormData;return t.append("action","edd_cancel_paypal_order"),fetch(edd_scripts.ajaxurl,{method:"POST",body:t}).then((function(e){return e.json()})).then((function(e){if(e.success){var t=e.data.nonces;Object.keys(t).forEach((function(e){var n=document.getElementById("edd-gateway-"+e);n&&n.setAttribute("data-"+e+"-nonce",t[e])}))}}))}};return eddPayPalVars.style&&(s.style=eddPayPalVars.style),s[u]=function(t,n){return d.style.display="block",o&&(o.innerHTML=""),fetch(edd_scripts.ajaxurl,{method:"POST",body:new FormData(r)}).then((function(e){return e.json()})).then((function(t){if(t.data&&t.data.paypal_order_id)return t.data.nonce&&(c.value=t.data.nonce),t.data.token&&(e(i).attr("data-token",t.data.token),e(i).attr("data-timestamp",t.data.timestamp)),t.data.paypal_order_id;var n=eddPayPalVars.defaultError;return t.data&&"string"==typeof t.data?n=t.data:"string"==typeof t&&(n=t),new Promise((function(e,t){t(n)}))}))},s}};e(document.body).on("edd_gateway_loaded",(function(e,n){"paypal_commerce"===n&&t.init()})),e(document).ready((function(e){for(var n=document.querySelectorAll(".edd-paypal-checkout-buy-now"),a=0;a<n.length;a++){var r=n[a];if(!r.classList.contains("edd-free-download")){var o=r.closest(".edd_purchase_submit_wrapper");if(o){o.innerHTML="";var d=document.createElement("div");d.classList.add("edd-paypal-checkout-buy-now-error-wrapper"),o.before(d);var c=document.createElement("span");c.classList.add("edd-paypal-spinner","edd-loading-ajax","edd-loading"),c.style.display="none",o.after(c),t.initButtons(o,"buy_now")}}}}))}).call(this,n(1))}});