jQuery(document).ready(function ($) {

    function replaceCSSBackgroundImageWPSS(jQObj){
        let thisBgImageURL = prevThisBgImageURL = jQObj.css('background-image');

        if(typeof thisBgImageURL == 'undefined' || !thisBgImageURL || thisBgImageURL == '' || thisBgImageURL == 'none'){

            return;
        }

        // console.log('thisBgImageURL', thisBgImageURL);
        let stop_replace = false;
        jQuery.each(WPSS_ALL_NEW_ATTACHMENT_URLS, function(kk, vv){
            if(`url("${vv}")` == thisBgImageURL){
                stop_replace = true;

                return false;
            }
        });

        if(stop_replace){
            return;
        }

        thisBgImageURL = thisBgImageURL.replace(WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL, WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL);
        if(prevThisBgImageURL == thisBgImageURL){
            return;
        }

        jQObj.css('background-image', thisBgImageURL);
    }

    function replaceIMGSRCWPSS(jQObj){
        let thisTagName = jQObj.prop('tagName');

        if(thisTagName != 'IMG'){
            return;
        }

        let thisBgImageURL = jQObj.attr('src');
        let prevThisBgImageURL = thisBgImageURL;

        if(typeof thisBgImageURL == 'undefined' || !thisBgImageURL || thisBgImageURL == '' || thisBgImageURL == 'none'){

            return;
        }

        // console.log('thisBgImageSRCURL', thisBgImageURL);

        thisBgImageURLWithoutProtocol = thisBgImageURL.replace('https://', '//');
        thisBgImageURLWithoutProtocol = thisBgImageURLWithoutProtocol.replace('http://', '//');

        let stop_replace = false;
        jQuery.each(WPSS_ALL_NEW_ATTACHMENT_URLS, function(kk, vv){
            vv = vv.replace('https://', '//');
            vv = vv.replace('http://', '//');

            if(vv == thisBgImageURLWithoutProtocol){
                stop_replace = true;

                return false;
            }
        });

        if(stop_replace){
            return;
        }

        thisBgImageURL = thisBgImageURL.replace(WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL, WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL);

        if(prevThisBgImageURL == thisBgImageURL){
            return;
        }

        jQObj.attr('src', thisBgImageURL);
    }

    function handleDOMChangedWPSS(thisJQObj){
        if(thisJQObj.prop('tagName') == 'IMG'){
            replaceIMGSRCWPSS(thisJQObj);
        } else {
            replaceCSSBackgroundImageWPSS(thisJQObj);
        }
    }

    function startMonitorDOMChangesWPSS(){
        observerWPSS = new MutationObserver((mutationList, observer) => {
            for (let mutation of mutationList) {
                let thisJQObj = jQuery(mutation.target);
                if (mutation.type === 'childList') {

                    // console.log('A child node has been added or removed.');

                    thisJQObj.children().each(function(thisIndex, thisElement){
                        let childJqObj = jQuery(thisElement);
                        handleDOMChangedWPSS(childJqObj);
                    });
                } else if (mutation.type === 'attributes') {

                    // console.log(`The ${mutation.attributeName} attribute was modified.`);

                    handleDOMChangedWPSS(thisJQObj);
                }
            }
        });
        let config = { attributes: true, childList: true, subtree: true };
        observerWPSS.observe(document, config);
    }

    setTimeout(function(){
        $('*').each(function (thisIndex, thisElement) {
            replaceCSSBackgroundImageWPSS(jQuery(thisElement));
            replaceIMGSRCWPSS(jQuery(thisElement));
        });
        startMonitorDOMChangesWPSS();
    }, 3000);

});
