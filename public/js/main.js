const POPUPS = new Popups();

// Function to get the contextual information inserted into <meta> tags by PHP
function getPHPMetaEntries() {
    const metaNames = ['queryStr', 'orderBy', 'autoFetchDetails', 'filterNonGeo', 'translateNonLatin', 'toggleLayout', 'toggleLanguage', 'pageNr'];

    // Extract meta information with exists check and build a dictgionary
    const metaEntries = {};
    metaNames.forEach(name => {
        const metaTag = document.querySelector(`meta[name="${name}"]`);
        if (metaTag) {
            // Cast to bool if "true" or "false", cast to float/number of float/number else string
            let content = metaTag.getAttribute('content').trim();

            if (content === 'true') {
                metaEntries[name] = true;
            } else if (content === 'false') {
                metaEntries[name] = false;
            } else if (!isNaN(content) && content !== '') {
                // Check if integer or float
                if (content.indexOf('.') !== -1) {
                    metaEntries[name] = parseFloat(content);
                } else {
                    metaEntries[name] = parseInt(content, 10);
                }
            } else {
                metaEntries[name] = content;
            }
        }
    });

    return metaEntries;
}

function modifyUrl(url, extension) {
    const urlObj = new URL(url);
    urlObj.search = "";
    let pathname = urlObj.pathname;
    if (!pathname.endsWith("/")) {
      pathname += "/";
    }
  
    urlObj.pathname = pathname;
  
    const baseUrlWithoutQuery = urlObj.origin + urlObj.pathname;
    const finalUrl = new URL(baseUrlWithoutQuery + extension);
  
    return finalUrl.toString();
}

// When new images are loaded do the following
function onNewImages() {
    
    // Add "translated" hover tooltips
    document.querySelectorAll('.translated-geonames').forEach(el => {
        const id = el.dataset.id;
        if (id) {
            // Add hover event
            el.onmouseenter = (e) => {
                // showAsPortal(elementId, x, y, originY = "top", originX = "left", nudgeOnScreen = true, closeOnClickOutside = true, closeOnMouseOut = true)
                // Get el position top center (at current scroll position)
                const rect = el.getBoundingClientRect();
                POPUPS.showAsPortal(`translated-geonames-${id}`, rect.left + (rect.width / 2), rect.top, "bottom", "center", true, true, true);
            };
            el.onmouseleave = (e) => {
                //MARK: To allow mousing the popup we should not close if mousepos is inside the portal, the issue is portal may be clone to use POPUPS.getElementOfPortal(id)
                POPUPS.hideAsPortal(`translated-geonames-${id}`);
            };
        }
    });

    // Add onclick to all ".img-fetch-geonames" elements
    document.querySelectorAll('.img-fetch-geonames').forEach(el => {
        el.onclick = async (e) => {
            const id = el.dataset.id;
            if (id) {
                const infoEl = document.querySelector(`.img-fetch-geonames-info[data-id="${id}"]`);

                // Ask /endpoints/getDetails.php?id=ID&filterNonGeo=<bool>&translateNonLatin=<bool>
                // Responds with {} or HTML
                const metaEntries = getPHPMetaEntries();

                const url = `endpoints/getDetails.php?id=${id}&filterNonGeo=${metaEntries.filterNonGeo ? 'true' : 'false'}&translateNonLatin=${metaEntries.translateNonLatin ? 'true' : 'false'}${metaEntries.toggleLanguage ? "&toggleLanguage" : ""}`;
                try {
                    const response = await fetch(modifyUrl(window.location.href, url))
                    // Is response OK?
                    if (!response.ok) {
                        // Get the .img-fetch-geonames-info under parent of el set its display to block and innerText to error
                        if (infoEl) {
                            infoEl.style.display = 'block';
                            infoEl.style.color = 'red';
                            infoEl.innerText = `Error: ${response.status} ${response.statusText}`;
                        }

                        return;
                    }

                    const text = await response.text();

                    // Is response JSON? If so parse and throw
                    // if first char is [ or { it is JSON
                    const firstChar = text.trim().charAt(0);
                    if (firstChar === '{' || firstChar === '[') {
                        
                        todisp = `Error: ${text}`;

                        try {
                            const json = JSON.parse(text);
                            if (json.error) {
                                todisp = `Error: ${json.error}`;
                            } else if (json.message) {
                                todisp = `Error: ${json.message}`;
                            }
                        } catch (e) {
                            // Ignore
                        }

                        

                        // Get the .img-fetch-geonames-info under parent of el set its display to block and innerText to error
                        if (infoEl) {
                            infoEl.style.display = 'block';
                            infoEl.style.color = 'red';
                            infoEl.innerText = todisp;
                        }

                        return;
                    }

                    // Get the closest .image-location-data of el and replace it with the response HTML
                    const locationDataEl = el.closest('.image-location-data');
                    if (locationDataEl) {
                        locationDataEl.outerHTML = text;
                    }

                    // Reset info text
                    if (infoEl) {
                        infoEl.style.display = 'none';
                        infoEl.style.color = '';
                        infoEl.innerText = '';
                    }

                } catch(e) {
                    if (infoEl) {
                        infoEl.style.display = 'block';
                        infoEl.style.color = 'red';
                        infoEl.innerText = `Error: ${e.message}`;
                    }
                }
            }
        };
    });

    // Add onclick to all ".embed-gmap-link" elements
    document.querySelectorAll('.embed-gmap-link').forEach(el =>{
        el.onclick = (e) => {
            const iframe = document.getElementById('iframe-interactive-map');
            iframe.src = el.dataset.url;
            POPUPS.showAsOverlay('gmaps-popup', closeOnClickOutside = true, closeOnMouseOut = false, darkenBackground = true);
        };
        document.getElementById("map-closer").onclick = (e) => {
            POPUPS.hideAsOverlay('gmaps-popup');
        };
    })
}

// When page is finished loading (PHP is done)
window.addEventListener('DOMContentLoaded', () => {
    // Add click listeners to settings buttons
    document.getElementById("settings-button").onclick = () => {
        POPUPS.showAsOverlay('settings', closeOnClickOutside = false, closeOnMouseOut = false, darkenBackground = true);
        console.log("working");
    };
    document.getElementById("settings-closer").onclick = () => {
        POPUPS.hideAsOverlay('settings')
    };

    // Add click handler to #get-more-images-button
    const moreImagesButton = document.getElementById('get-more-images-button');
    moreImagesButton.onclick = async (e) => {
        // Ask /endpoints/getPage.php?queryStr=<urlrawencoded-string>&pageNr=<int>&orderBy=<enum:orderBy>&autoFetchDetails=<bool>&filterNonGeo=<bool>&translateNonLatin=<bool>
        //     enum:orderBy = relevant | latest
        // Responds with {} or HTML
        const metaEntries = getPHPMetaEntries();
        const nextPageNr = (metaEntries.pageNr && !isNaN(metaEntries.pageNr)) ? (parseInt(metaEntries.pageNr, 10) + 1) : 2;
        const url = `endpoints/getPage.php?queryStr=${encodeURIComponent(metaEntries.queryStr || '')}&pageNr=${nextPageNr}&orderBy=${metaEntries.orderBy || 'relevant'}&autoFetchDetails=${metaEntries.autoFetchDetails ? 'true' : 'false'}&filterNonGeo=${metaEntries.filterNonGeo ? 'true' : 'false'}&translateNonLatin=${metaEntries.translateNonLatin ? 'true' : 'false'}${metaEntries.toggleLanguage ? "&toggleLanguage" : ""}`;

        const infoEl = document.getElementById('get-more-images-info');
        try {
            moreImagesButton.disabled = true;
            if (infoEl) {
                infoEl.style.display = 'block';
                infoEl.style.color = '';
                infoEl.innerText = 'Loading...';
            }
            
            const response = await fetch(modifyUrl(window.location.href, url))
            
            // Is response OK?
            if (!response.ok) {
                // Set infoEl display to block and innerText to error
                if (infoEl) {
                    infoEl.style.display = 'block';
                    infoEl.style.color = 'red';
                    infoEl.innerText = `Error: ${response.status} ${response.statusText}`;
                }
                moreImagesButton.disabled = false;
                return;
            }

            const text = await response.text();

            // Is response JSON? If so parse and throw
            // if first char is [ or { it is JSON
            const firstChar = text.trim().charAt(0);
            if (firstChar === '{' || firstChar === '[') {
                todisp = `Error: ${text}`;

                try {
                    const json = JSON.parse(text);
                    if (json.error) {
                        todisp = `Error: ${json.error}`;
                    } else if (json.message) {
                        todisp = `Error: ${json.message}`;
                    }
                } catch (e) {
                    // Ignore
                }

                // Set infoEl display to block and innerText to error
                if (infoEl) {
                    infoEl.style.display = 'block';
                    infoEl.style.color = 'red';
                    infoEl.innerText = todisp;
                }
                moreImagesButton.disabled = false;
                return;
            }

            // Endpoint resonds with HTML to be appended into #image-container
            const imageContainer = document.getElementById('image-container');
            if (imageContainer) {
                imageContainer.insertAdjacentHTML('beforeend', text);
            }
            // Update meta tag pageNr to nextPageNr
            const pageNrMeta = document.querySelector('meta[name="pageNr"]');
            if (pageNrMeta) {
                pageNrMeta.setAttribute('content', nextPageNr.toString());
            }

            // Ensure event handlers
            onNewImages();

            // Reset info text
            if (infoEl) {
                infoEl.style.display = 'none';
                infoEl.style.color = '';
                infoEl.innerText = '';
            }
            moreImagesButton.disabled = false;

        } catch(e) {
            if (infoEl) {
                infoEl.style.display = 'block';
                infoEl.style.color = 'red';
                infoEl.innerText = `Error: ${e.message}`;
            }
            return;
        }
    };

    // Initial
    onNewImages();
});