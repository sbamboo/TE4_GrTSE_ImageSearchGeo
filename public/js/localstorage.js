class LocalStorageHandler {
    constructor() {
        this.accepted = false;
        if (localStorage.getItem('accepted') === 'true') {
            this.accepted = true;
        }
        this.local = new Object();
    }

    Accept() {
        this.accepted = true;
        localStorage.setItem('accepted', 'true');
        this.MigrateAllFromLocalToLocalStorage();
    }

    Revoke() {
        this.accepted = false;
        localStorage.removeItem('accepted');
        this.RevertAllFromLocalStorageToLocal();
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