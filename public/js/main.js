const POPUPS = new Popups();

const TAGS = [];

let markers = [];
let openInfoWindow = null;
let map = null;
let markerQueue = [];
let infoWindow = null;


function addImageMarker(imageUrl, lat, lng, placeName = "") {
    if (!window.google || !google.maps || !google.maps.marker) {
        // Queue until Maps API is ready
        window._pendingMarkers = window._pendingMarkers || [];
        window._pendingMarkers.push([imageUrl, lat, lng, placeName]);
        return;
      }

    const latNum = parseFloat(lat);
    const lngNum = parseFloat(lng);

    if (isNaN(latNum) || isNaN(lngNum)) {
        return;
    }

    const img = document.createElement("img");
    img.src = imageUrl;
    img.style.width = "50px";
    img.style.height = "50px";
    img.style.objectFit = "cover";
    img.style.borderRadius = "50%";
    img.style.boxShadow = "0 0 5px rgba(0,0,0,0.4)";

    const marker = new google.maps.marker.AdvancedMarkerElement({
        position: { lat: latNum, lng: lngNum },
        map: window.map,
        content: img,
    });

    const infowindow = new google.maps.InfoWindow({
        content: `<img src="${imageUrl}" width="200"><br><b>${placeName}</b>`,
    });

    marker.addListener("click", () => {
        if (openInfoWindow) openInfoWindow.close();
        infowindow.open({ anchor: marker, map: window.map });
        openInfoWindow = infowindow;
    });

    markers.push(marker);
}

function refreshMap() {
    if (typeof initMap === 'function') {
        initMap();
    }
}
function clearMarkers() {
    markers.forEach(m => m.setMap(null));
    markers = [];
}



// Function to get the contextual information inserted into <meta> tags by PHP
function getPHPMetaEntries() {

    const metaNames = ['queryStr', 'orderBy', 'autoFetchDetails', 'filterNonGeo', 'translateNonLatin', 'toggleLayout', 'toggleLanguage', 'pageNr', 'imagesPerPage', 'embedGMaps', 'cachedTags', 'highlightTags', 'toggleMapMode', 'imagesCount'];

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

    // Add listeners for progressive images
    document.querySelectorAll('.progressive-image').forEach((el) => {
        el.onload = (e)=>{
            // Because of the double call (ensuring call) bellow make sure we are not doing this twice
            const isSwapped = el.dataset.swapped;
            if (isSwapped === "true") return;

            const fullsrc = el.dataset.fullsrc;
            const full = new Image();
            full.src = fullsrc;
            full.decode().then(() => { 
                el.src = full.src; 
                el.dataset.swapped = "true";
                // swap here
                addImageMarker(el.src);
                
            });
        }
        // Incase image has loaded before this segment of code just run it again to ensure its swapped.
        if (el.complete) {
            el.onload();
        }
    });

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

                // Ask /endpoints/getdetails.php?id=ID&filterNonGeo=<bool>&translateNonLatin=<bool>
                // Responds with {} or HTML
                const metaEntries = getPHPMetaEntries();

                const url = `endpoints/getdetails.php?id=${id}&filterNonGeo=${metaEntries.filterNonGeo ? 'true' : 'false'}&translateNonLatin=${metaEntries.translateNonLatin ? 'true' : 'false'}${metaEntries.toggleLanguage ? "&toggleLanguage" : ""}${metaEntries.embedGMaps ? "&embedGMaps" : ""}`;
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
                    //console.log("BOO")
                    if (locationDataEl) {
                        locationDataEl.outerHTML = text;
                        const imageContainers = document.querySelectorAll('.image-container');
                        if(imageContainers){
                            imageContainers.forEach(imageContainer => {
                                const imageLocationData = imageContainer.querySelector('.image-location-data');
                                const imagePlaceData = imageContainer.querySelector('.location-text');
                                if (!imageLocationData) return;

                                const lat = parseFloat(imageLocationData.dataset.lat);
                                const lon = parseFloat(imageLocationData.dataset.lon);
                                const place = imageLocationData.dataset.place;
                                // const city = imagePlaceData.dataset.city;
                                // const country = imagePlaceData.dataset.country;

                                const imageEl = imageContainer.querySelector('.image img');
                                const imageUrl = imageEl ? imageEl.dataset.fullsrc : '';

                                //console.log(place);
                                if (!isNaN(lat) && !isNaN(lon) && imageUrl) {
                                    addImageMarker(imageUrl, lat, lon, place);
                                }
                            });
                        }
                    }
                    //console.log(locationDataEl)

                    // If locationDataEl now has data-gmaps get .embed-gmap-link inside locationDataEl's parent and set its data-url to it
                    const imageContainer = document.querySelector(`.image-container[data-id="${id}"]`);
                    const newLocationDataEl = imageContainer ? imageContainer.querySelector('.image-location-data') : null;
                    //console.log(newLocationDataEl)
                    if (newLocationDataEl && newLocationDataEl.dataset.gmaps) {
                        const gmapEmbedLink = newLocationDataEl.parentElement.querySelector('.embed-gmap-link');
                        //console.log(gmapEmbedLink);
                        if (gmapEmbedLink) {
                            gmapEmbedLink.dataset.url = newLocationDataEl.dataset.gmaps;
                        } else {
                            const gmapLink = newLocationDataEl.parentElement.querySelector('.image-photo-gmaps-link');
                            //console.log(gmapLink);
                            if (gmapLink) {
                                gmapLink.href = newLocationDataEl.dataset.gmaps;
                            }
                        }
                    }

                    // Update TAGS with the tags from the new location data
                    if (newLocationDataEl) {
                        const tagsData = newLocationDataEl.dataset.tags;
                        if (tagsData) {
                            // tagsData is a comma separated list of tags ', '
                            const _tags = tagsData.split(',');
                            _tags.forEach(tag => {
                                const trimmed = tag.trim();
                                if (trimmed && !TAGS.includes(trimmed)) {
                                    //console.log("Non auto fetch Adding tag", trimmed);
                                    TAGS.push(trimmed);
                                }
                            });
                        }
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


    const imageContainers = document.querySelectorAll('.image-container');
    if(imageContainers){
        imageContainers.forEach(imageContainer => {
            const imageLocationData = imageContainer.querySelector('.image-location-data');
            const imagePlaceData = imageContainer.querySelector('.location-text');
            if (!imageLocationData) return;

            const lat = parseFloat(imageLocationData.dataset.lat);
            const lon = parseFloat(imageLocationData.dataset.lon);
            const place = imageLocationData.dataset.place;
            // const city = imagePlaceData.dataset.city;
            // const country = imagePlaceData.dataset.country;

            const imageEl = imageContainer.querySelector('.image img');
            const imageUrl = imageEl ? imageEl.dataset.fullsrc : '';

            //console.log(place);
            if (!isNaN(lat) && !isNaN(lon) && imageUrl) {
                addImageMarker(imageUrl, lat, lon, place);
            }
        });
    }

    // update tags
    const metaEntries = getPHPMetaEntries();
    if (metaEntries.cachedTags) {
        // cachedTags is a comma separated list of tags ', '
        const _tags = metaEntries.cachedTags.split(',');
        _tags.forEach(tag => {
            const trimmed = tag.trim();
            if (trimmed && !TAGS.includes(trimmed)) {
                //console.log("meta Adding tag", trimmed);
                TAGS.push(trimmed);
            }
        });

        
    }
    const imageLocationDatas = document.querySelectorAll('.image-location-data');
    imageLocationDatas.forEach(el => {
        const tagsData = el.dataset.tags;
        if (tagsData) {
            // tagsData is a comma separated list of tags ', '
            const _tags = tagsData.split(',');
            _tags.forEach(tag => {
                const trimmed = tag.trim();
                if (trimmed && !TAGS.includes(trimmed)) {
                    //console.log("Initial Adding tag", trimmed);
                    TAGS.push(trimmed);
                }
            });
        }
    });

}

// When page is finished loading (PHP is done)
//MARK: "load" or "DOMContentLoaded"? Took "load" to not await responsive-images
window.addEventListener('load', () => {
    // Conditionally show localstorage consent popup
    if (!STORAGE.IsAccepted() && (getPHPMetaEntries().queryStr === null || getPHPMetaEntries().queryStr.trim().length === 0)) {
        POPUPS.showAsOverlay('localstorage-prompt', closeOnClickOutside = false, closeOnMouseOut = false, darkenBackground = true);
    }
    if (STORAGE.IsAccepted()) {
        document.documentElement.setAttribute('data-localstorage-consent', 'true');
    } else {
        document.documentElement.setAttribute('data-localstorage-consent', 'false');
    }
    document.getElementById("localstorage-accept").onclick = () => {
        STORAGE.Accept();
        POPUPS.hideAsOverlay('localstorage-prompt');
    };
    document.getElementById("localstorage-decline").onclick = () => {
        STORAGE.Revoke();
        POPUPS.hideAsOverlay('localstorage-prompt');
    };

    // Set settings listers for consent
    document.getElementById("accept-localstorage-consent").onclick = () => {
        STORAGE.Accept();
    };
    document.getElementById("revoke-localstorage-consent").onclick = () => {
        POPUPS.hideAsOverlay('settings');
        STORAGE.Revoke();
        //MARK: Should we show the prompt again?
        //POPUPS.showAsOverlay('localstorage-prompt', closeOnClickOutside = false, closeOnMouseOut = false, darkenBackground = true);
    };

    // Add click listeners to settings buttons)
    document.getElementById("settings-button").onclick = () => {
        POPUPS.showAsOverlay('settings', closeOnClickOutside = false, closeOnMouseOut = false, darkenBackground = true);
    };
    document.getElementById("settings-closer").onclick = () => {
        POPUPS.hideAsOverlay('settings')
    };

    // Add click handler to #get-more-images-button
    const moreImagesButton = document.getElementById('get-more-images-button');
    if (moreImagesButton) {
        moreImagesButton.onclick = async (e) => {
            // Ask /endpoints/getpage.php?queryStr=<urlrawencoded-string>&pageNr=<int>&orderBy=<enum:orderBy>&autoFetchDetails=<bool>&filterNonGeo=<bool>&translateNonLatin=<bool>
            //     enum:orderBy = relevant | latest
            // Responds with {} or HTML
            const metaEntries = getPHPMetaEntries();
            const nextPageNr = (metaEntries.pageNr && !isNaN(metaEntries.pageNr)) ? (parseInt(metaEntries.pageNr, 10) + 1) : 2;
            const url = `endpoints/getpage.php?queryStr=${encodeURIComponent(metaEntries.queryStr || '')}&pageNr=${nextPageNr}&orderBy=${metaEntries.orderBy || 'relevant'}&autoFetchDetails=${metaEntries.autoFetchDetails ? 'true' : 'false'}&filterNonGeo=${metaEntries.filterNonGeo ? 'true' : 'false'}&translateNonLatin=${metaEntries.translateNonLatin ? 'true' : 'false'}${metaEntries.toggleLanguage ? "&toggleLanguage" : ""}${metaEntries.embedGMaps ? "&embedGMaps" : ""}${metaEntries.imagesPerPage ? `&imagesPerPage=${metaEntries.imagesPerPage}` : ""}`;

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

                // Endpoint resonds with HTML to be appended into #search-result-container
                const imageContainer = document.getElementById('search-result-container');
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
    }

    // Initial
    onNewImages();
});

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("search-bar");
    const mirror = document.getElementById("higlight");

    if (!input || !mirror) return;

    //const customWords = ["landscape", "portrait", "squarish", "black_and_white"];

    // check if string is a valid CSS color
    function isColor(str) {
        if (!str) return false;
        const s = new Option().style;
        s.color = str.toLowerCase();
        return s.color !== "";
    }

    // Copy input styles to mirror
    const inputStyle = window.getComputedStyle(input);
    const styleProps = [
        "font-family", "font-size", "font-weight", "line-height",
        "padding", "border", "border-radius", "box-sizing",
        "width", "height"
    ];
    styleProps.forEach(prop => {
        mirror.style[prop] = inputStyle.getPropertyValue(prop);
    });

    // Reduce the right side slightly (e.g., 1rem smaller than input)
    mirror.style.height = `calc(${inputStyle.getPropertyValue("height")} - 0.01rem)`;
    mirror.style.width = `calc(${inputStyle.getPropertyValue("width")} - 1rem)`;
    mirror.style.border = "none"; // No border on mirror

    // Ensure it stays aligned
    mirror.style.position = "absolute";
    mirror.style.top = "0";
    mirror.style.left = "0.02rem"; // Slightly right to avoid input border

    function updateMirror() {
        const words = input.value.split(/(\s+)/); // keep spaces
        const result = words.map(word => {
            const clean = word.trim();
            if (!clean) return word;
            const metaEntries = getPHPMetaEntries();
            const doHighlight = metaEntries.highlightTags === true;
            
            if (doHighlight == true) {
               var $backgroundVis = "var(--highlight-color-color)";
               var $backgroundMarkVis = "var(--highlight-mark-color)";
               var $colorVis = "color: var(--text-color)";
                //console.log("highlighting");
            }
            else {
                var $backgroundVis = "transparent";
                var $backgroundMarkVis = "transparent";
                var $colorVis = "transparent";
                //console.log("no highlight");
            }
            if (isColor(clean)) {
                // returns <span class="color-word" style="background-color: in $backgroundVis; color: in $colorVis">${word}</span>

                return '<span class="color-word" style="background-color: ' + $backgroundVis + '; color: ' + $colorVis + '">'+ word +'</span>';
            }
            if (TAGS.includes(clean.toLowerCase())) {
                return '<mark style="color: '+ $colorVis +'; background-color:'+ $backgroundMarkVis +'">'+ word +'</mark>';
            }


            
            // Keep the word but make it invisible
            return `<span class="hidden-word">${word}</span>`;
        }).join("");
    

        mirror.innerHTML = result || "&nbsp;";
        mirror.scrollLeft = input.scrollLeft;
    }

    updateMirror();

    input.addEventListener("input", updateMirror);
    input.addEventListener("scroll", () => {
        mirror.scrollLeft = input.scrollLeft;
    });
});

