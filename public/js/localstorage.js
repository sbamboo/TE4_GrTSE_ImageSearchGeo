class LocalStorageHandler {
    constructor(onAccept = null, onRevoke = null) {
        this.accepted = false;
        if (localStorage.getItem('accepted') === 'true') {
            this.accepted = true;
        }
        this.local = new Object();

        // Create custom events for accept and revoke and subscribe if given
        this.onAccept = [];
        this.onRevoke = [];
        if (onAccept) {
            this.onAccept.push(onAccept);
        }
        if (onRevoke) {
            this.onRevoke.push(onRevoke);
        }
    }

    AddOnAcceptCallback(callback) {
        this.onAccept.push(callback);
    }
    AddOnRevokeCallback(callback) {
        this.onRevoke.push(callback);
    }
    RemoveOnAcceptCallback(callback) {
        this.onAccept = this.onAccept.filter(cb => cb !== callback);
    }
    RemoveOnRevokeCallback(callback) {
        this.onRevoke = this.onRevoke.filter(cb => cb !== callback);
    }

    Accept() {
        this.accepted = true;
        localStorage.setItem('accepted', 'true');
        this.MigrateAllFromLocalToLocalStorage();
        if (this.onAccept && this.onAccept.length > 0) {
            this.onAccept.forEach(cb => cb());
        }
    }

    Revoke() {
        this.accepted = false;
        localStorage.removeItem('accepted');
        this.RevertAllFromLocalStorageToLocal();
        if (this.onRevoke && this.onRevoke.length > 0) {
            this.onRevoke.forEach(cb => cb());
        }
    }

    IsAccepted() {
        return this.accepted;
    }

    Set(key, value) {
        if (!this.accepted) {
            this.local[key] = value;
            return;
        }

        localStorage.setItem(key, JSON.stringify(value));
    }

    Get(key) {
        if (!this.accepted) {
            return this.local[key];
        }

        const item = localStorage.getItem(key);
        if (item) {
            try {
                return JSON.parse(item);
            } catch (e) {
                return item;
            }
        }
        return null;
    }

    Has(key) {
        if (!this.accepted) {
            return key in this.local;
        }

        return localStorage.getItem(key) !== null;
    }

    Remove(key) {
        if (!this.accepted) {
            delete this.local[key];
            return;
        }

        localStorage.removeItem(key);
    }

    StoreIfAbsent(key, value) {
        if (!this.has(key)) {
            this.set(key, value);
        }
    }

    MigrateAllFromLocalToLocalStorage() {
        if (!this.accepted) {
            // If user hasn’t accepted yet, do nothing.
            return;
        }
    
        for (const [key, value] of Object.entries(this.local)) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.error(`Failed to migrate key "${key}"`, e);
            }
        }
    
        // Once migrated, clear the in-memory store
        this.local = {};
    }

    RevertAllFromLocalStorageToLocal() {
        if (this.accepted) {
            // If user has accepted, do nothing.
            return;
        }
    
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key) {
                const item = localStorage.getItem(key);
                if (item) {
                    try {
                        this.local[key] = JSON.parse(item);
                    } catch (e) {
                        this.local[key] = item;
                    }
                }
            }
        }
    
        // Once reverted, clear localStorage
        localStorage.clear();
    }

    ClearAll() {
        this.local = {};
        if (this.accepted) {
            localStorage.clear();
        }
    }
}

const STORAGE = new LocalStorageHandler(
    onAccept = ()=>{
        document.documentElement.setAttribute('data-localstorage-consent', 'true');
    },
    onRevoke = ()=>{
        document.documentElement.setAttribute('data-localstorage-consent', 'false');
    }
);


window.addEventListener('load', () => {
    // Apply localstorage to seach bar
    const orderBy = document.getElementById('order-by'); // Element of type select with option values "relevant" and "latest"
    const cbToggleLanguage = document.getElementById('toggle-language');
    const cbToggleLayout = document.getElementById('toggle-layout');

    if (STORAGE.Has("setting.orderBy") && orderBy) { orderBy.value = STORAGE.Get("setting.orderBy"); }
    if (STORAGE.Has("setting.toggleLanguage") && cbToggleLanguage) { cbToggleLanguage.checked = STORAGE.Get("setting.toggleLanguage") === true; }
    if (STORAGE.Has("setting.toggleLayout") && cbToggleLayout) { cbToggleLayout.checked = STORAGE.Get("setting.toggleLayout") === true; }
    
    if (orderBy) {
        orderBy.onchange = (e) => { STORAGE.Set("setting.orderBy", orderBy.value); };
    }
    if (cbToggleLanguage) {
        cbToggleLanguage.onchange = (e) => {
            STORAGE.Set("setting.toggleLanguage", cbToggleLanguage.checked === true);
            //this.form.submit();
            // Submit form
            const form = document.getElementById('search-form');
            if (form) form.submit();
        };
    }
    if (cbToggleLayout) {
        cbToggleLayout.onchange = (e) => { STORAGE.Set("setting.toggleLayout", cbToggleLayout.checked === true); };
    }
    

    // Apply localstorage to settings
    const onAcceptedAndLoaded = () => {
        const cbAutoFetchDetails = document.getElementById('auto-fetch-details');
        const cbFilterNonGeo = document.getElementById('filter-non-geo');
        const cbTranslateNonLatin = document.getElementById('translate-non-latin');
        const cbEmbedGmaps = document.getElementById('embed-gmaps');
        const cbHighlightTags = document.getElementById('highlight-tags');

        if (STORAGE.Has("setting.autoFetchDetails") && cbAutoFetchDetails) { cbAutoFetchDetails.checked = STORAGE.Get("setting.autoFetchDetails") === true; }
        if (STORAGE.Has("setting.filterNonGeo") && cbFilterNonGeo) { cbFilterNonGeo.checked = STORAGE.Get("setting.filterNonGeo") === true; }
        if (STORAGE.Has("setting.translateNonLatin") && cbTranslateNonLatin) { cbTranslateNonLatin.checked = STORAGE.Get("setting.translateNonLatin") === true; }
        if (STORAGE.Has("setting.embedGmaps") && cbEmbedGmaps) { cbEmbedGmaps.checked = STORAGE.Get("setting.embedGmaps") === true; }
        if (STORAGE.Has("setting.highlightTags") && cbHighlightTags) { cbHighlightTags.checked = STORAGE.Get("setting.highlightTags") === true; }

        if (cbAutoFetchDetails) {
            cbAutoFetchDetails.onchange = (e) => { STORAGE.Set("setting.autoFetchDetails", cbAutoFetchDetails.checked === true); };
        }
        if (cbFilterNonGeo) {
            cbFilterNonGeo.onchange = (e) => { STORAGE.Set("setting.filterNonGeo", cbFilterNonGeo.checked === true); };
        }
        if (cbTranslateNonLatin) {
            cbTranslateNonLatin.onchange = (e) => { STORAGE.Set("setting.translateNonLatin", cbTranslateNonLatin.checked === true); };
        }
        if (cbEmbedGmaps) {
            cbEmbedGmaps.onchange = (e) => { STORAGE.Set("setting.embedGmaps", cbEmbedGmaps.checked === true); };
        }
        if (cbHighlightTags) {
            cbHighlightTags.onchange = (e) => { STORAGE.Set("setting.highlightTags", cbHighlightTags.checked === true); };
        }
    };
    if (!STORAGE.IsAccepted()) {
        STORAGE.AddOnAcceptCallback(onAcceptedAndLoaded);
    } else {
        onAcceptedAndLoaded();
    }
});