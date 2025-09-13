// File containig reusable code for popups

window.hasAssignedBodyClickListener = false; // Global variable to track if body click listener is assigned

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

        // Global click outside listener by us assinging a listener to body if one is not already asigned
        if (!window.hasAssignedBodyClickListener) {
            document.body.addEventListener('click', (event) => {
                // Get all elements where we clicked using elementsFromPoint
                const elementsAtClick = document.elementsFromPoint(event.clientX, event.clientY);
                const clickedPopup = this.shownPopups.find(popup => elementsAtClick.includes(popup));
                const clickedPortal = this.shownPortals.find(portal => elementsAtClick.includes(portal));

                // If we clicked outside any shown popup or portal, hide all popups and portals that are set to close on click outside
                if (!clickedPopup) {
                    this.shownPopups = this.shownPopups.filter(popup => {
                        if (popup.dataset.closeOnClickOutside === 'true') {
                            popup.style.display = 'none';
                            return false; // Remove from shownPopups
                        }
                        return true; // Keep in shownPopups
                    });
                }

                if (!clickedPortal) {
                    this.shownPortals = this.shownPortals.filter(portal => {
                        if (portal.dataset.closeOnClickOutside === 'true') {
                            portal.style.display = 'none';
                            return false; // Remove from shownPortals
                        }
                        return true; // Keep in shownPortals
                    });
                }
            });

            window.hasAssignedBodyClickListener = true; // Mark listener as assigned
        }
    }

    // Show a overlay popup (A popup that is above all content and centered on the screen)
    showAsOverlay(elementId, closeOnClickOutside=false, closeOnMouseOut=false) {
        // If element is not child of overlay container, clone it and append it
        // Add data attributes for closeOnClickOutside and closeOnMouseOut
        // If closeOnMouseOut is true, add mouseout listener to hide popup when mouse leaves it
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
        // Add data attributes for closeOnClickOutside and closeOnMouseOut
        // If closeOnMouseOut is true, add mouseout listener to hide popup when mouse leaves it
    }

    // Hide a portal popup
    hideAsPortal(elementId) {
        
    }

    // Hide all portal popups
    hideAllPortals() {
        // Reverse iterate to avoid child issues
    }
}
