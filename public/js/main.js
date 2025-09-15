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

// When page is finished loading (PHP is done)
window.addEventListener('DOMContentLoaded', () => {
    // Add "translated" hover tooltips
    document.querySelectorAll('.translated-geonames').forEach(el => {
        const id = el.dataset.id;
        if (id) {
            // Add hover event
            el.addEventListener('mouseenter', (e) => {
                // showAsPortal(elementId, x, y, originY = "top", originX = "left", nudgeOnScreen = true, closeOnClickOutside = true, closeOnMouseOut = true)
                // Get el position top center (at current scroll position)
                const rect = el.getBoundingClientRect();
                POPUPS.showAsPortal(`translated-geonames-${id}`, rect.left + (rect.width / 2), rect.top, "bottom", "center", true, true, true);
            });
            el.addEventListener('mouseleave', (e) => {
                //MARK: To allow mousing the popup we should not close if mousepos is inside the portal, the issue is portal may be clone to use POPUPS.getElementOfPortal(id)
                POPUPS.hideAsPortal(`translated-geonames-${id}`);
            });

        }
        document.getElementById("settings-button").addEventListener("click", () => {
            POPUPS.showAsOverlay('settings', closeOnClickOutside = true, closeOnMouseOut = false, darkenBackground = true);
            /*console.log("working")*/
        });
    });
    
    });

    // Add onclick to all ".img-fetch-geonames" elements
    document.querySelectorAll('.img-fetch-geonames').forEach(el => {
        el.addEventListener('click', async (e) => {
            const id = el.dataset.id;
            if (id) {
                // Ask /endpoints/getDetails.php?id=ID&filterNonGeo=<bool>&translateNonLatin=<bool>
                // Responds with {} or HTML
                const metaEntries = getPHPMetaEntries();
                const url = `/endpoints/getDetails.php?id=${id}&filterNonGeo=${metaEntries.filterNonGeo ? 'true' : 'false'}&translateNonLatin=${metaEntries.translateNonLatin ? 'true' : 'false'}`;
                try {
                    const response = await fetch(url)
                    // Is response OK?
                    if (!response.ok) {
                        // Get the .img-fetch-geonames-info under parent of el set its display to block and innerText to error
                        const infoEl = document.querySelector(`.img-fetch-geonames-info[data-id="${id}"]`);
                        console.log(infoEl);
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
                        // Get the .img-fetch-geonames-info under parent of el set its display to block and innerText to error
                        const infoEl = document.querySelector(`.img-fetch-geonames-info[data-id="${id}"]`);
                        console.log(infoEl);
                        if (infoEl) {
                            infoEl.style.display = 'block';
                            infoEl.style.color = 'red';
                            infoEl.innerText = `Error: ${text.trim()}`;
                        }

                        return;
                    }

                    // Get the closest .image-location-data of el and replace it with the response HTML
                    const locationDataEl = el.closest('.image-location-data');
                    if (locationDataEl) {
                        locationDataEl.outerHTML = text;
                    }

                    // Reset info text
                    const infoEl = document.querySelector(`.img-fetch-geonames-info[data-id="${id}"]`);
                    console.log(infoEl);
                    if (infoEl) {
                        infoEl.style.display = 'none';
                        infoEl.style.color = '';
                        infoEl.innerText = '';
                    }

                } catch(e) {
                    const infoEl = document.querySelector(`.img-fetch-geonames-info[data-id="${id}"]`);
                    console.log(infoEl);
                    if (infoEl) {
                        infoEl.style.display = 'block';
                        infoEl.style.color = 'red';
                        infoEl.innerText = `Error: ${e.message}`;
                    }
                }
            }
        });
    });
});