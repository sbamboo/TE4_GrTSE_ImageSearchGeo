// File containig reusable code for popups

class Popups {
    constructor() {
        // If DOM has not loaded schedule constructor to run on DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.constructor());
            return;
        }

        // Get containers
        this.overlayContainer = document.getElementById('overlay-container');
        this.popupContainer = document.getElementById('popup-container');
        this.portalContainer = document.getElementById('portal-container');

        this.shownPopups = []; // Stores elements
        this.shownPortals = []; // Stores elements
    }

    // Show a overlay popup (A popup that is above all content and centered on the screen)
    showAsOverlay(elementId, closeOnClickOutside=false, closeOnMouseOut=false) {
        // If element is not child of overlay container, clone it and append it
    }

    // Hide a overlay popup
    hideAsOverlay(elementId) {
        
    }

    // Hide all overlay popups
    hideAllOverlays() {
        // Reverse iterate to avoid child issues
    }

    // Show a portal popup (A popup that is placed at a location, like a right click menu, or a tooltip)
    showAsPortal(elementId, x, y, originY = "top", originX = "left", nudgeOnScreen = true, closeOnClickOutside=true, closeOnMouseOut=true) {
        // OriginY: top, center, bottom
        // OriginX: left, center, right

        // If element is not child of overlay container, clone it and append it
    }

    // Hide a portal popup
    hideAsPortal(elementId) {
        
    }

    // Hide all portal popups
    hideAllPortals() {
        // Reverse iterate to avoid child issues
    }
}
