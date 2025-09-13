// When page is finished loading (PHP is done)
window.addEventListener('DOMContentLoaded', () => {

    // Popup testers
    const popupContainer = document.getElementById('popup-container');
    const portalContainer = document.getElementById('portal-container');

    const popup = document.createElement('div');
    popup.id = 'popup';
    popup.classList.add('popup');
    popup.style.display = 'none';
    popup.innerHTML = `
        <h2>Popup</h2>
        <p>This is a popup element.</p>
    `;
    popupContainer.appendChild(popup);

    const popupClickOutside = document.createElement('div');
    popupClickOutside.id = 'popup-click-outside';
    popupClickOutside.classList.add('popup');
    popupClickOutside.style.display = 'none';
    popupClickOutside.innerHTML = `
        <h2>Popup Click Outside</h2>
        <p>This popup closes when clicking outside of it.</p>
    `;
    popupContainer.appendChild(popupClickOutside);

    const popupHoverOutside = document.createElement('div');
    popupHoverOutside.id = 'popup-hover-outside';
    popupHoverOutside.classList.add('popup');
    popupHoverOutside.style.display = 'none';
    popupHoverOutside.innerHTML = `
        <h2>Popup Hover Outside</h2>
        <p>This popup closes when mouse leaves it.</p>
    `;
    popupContainer.appendChild(popupHoverOutside);

    const portal = document.createElement('div');
    portal.id = 'portal';
    portal.classList.add('portal');
    portal.style.display = 'none';
    portal.innerHTML = `
        <h2>Portal</h2>
        <p>This is a portal element.</p>
    `;
    portalContainer.appendChild(portal);

    const portalClickOutside = document.createElement('div');
    portalClickOutside.id = 'portal-click-outside';
    portalClickOutside.classList.add('portal');
    portalClickOutside.style.display = 'none';
    portalClickOutside.innerHTML = `
        <h2>Portal Click Outside</h2>
        <p>This portal closes when clicking outside of it.</p>
    `;
    portalContainer.appendChild(portalClickOutside);

    const portalHoverOutside = document.createElement('div');
    portalHoverOutside.id = 'portal-hover-outside';
    portalHoverOutside.classList.add('portal');
    portalHoverOutside.style.display = 'none';
    portalHoverOutside.innerHTML = `
        <h2>Portal Hover Outside</h2>
        <p>This portal closes when mouse leaves it.</p>
    `;
    portalContainer.appendChild(portalHoverOutside);


    // Insert css for popups and portals
    const style = document.createElement('style');
    style.textContent = `
        .popup, .portal {
            background-color: white;
            color: black;
            border: 1px solid black;
            padding: 10px;
        }
    `;
    document.head.appendChild(style);


    // Inject buttons to open popups and portals
    const POPUPS = new Popups();

    const popupButton = document.createElement('button');
    popupButton.textContent = 'Open Popup';
    popupButton.onclick = (event) => {
        event.preventDefault();
        POPUPS.showAsOverlay("popup", closeOnClickOutside=false, closeOnMouseOut=false, darkenBackground=false);
        //POPUPS.showAsPortal("portal", event.clientX, event.clientY, originY="top", originX="right", nudgeOnScreen=true, closeOnClickOutside=false, closeOnMouseOut=false);
        //POPUPS.showAsPortal("portal", event.clientX, event.clientY, originY="top", originX="right", nudgeOnScreen=false, closeOnClickOutside=false, closeOnMouseOut=false);
    };
    document.body.appendChild(popupButton);

    const popupClickOutsideButton = document.createElement('button');
    popupClickOutsideButton.textContent = 'Open Popup Click Outside';
    popupClickOutsideButton.onclick = (event) => {
        event.preventDefault();
        POPUPS.showAsOverlay("popup-click-outside", closeOnClickOutside=true, closeOnMouseOut=false, darkenBackground=false);
    };
    document.body.appendChild(popupClickOutsideButton);

    const popupHoverOutsideButton = document.createElement('button');
    popupHoverOutsideButton.textContent = 'Open Popup Hover Outside';
    popupHoverOutsideButton.onclick = (event) => {
        event.preventDefault();
        POPUPS.showAsOverlay("popup-hover-outside", closeOnClickOutside=false, closeOnMouseOut=true, darkenBackground=true);
    };
    document.body.appendChild(popupHoverOutsideButton);

    const portalButton = document.createElement('button');
    portalButton.textContent = 'Open Portal';
    portalButton.onclick = (event) => {
        POPUPS.showAsPortal("portal", event.clientX, event.clientY, originY="top", originX="left", nudgeOnScreen=true, closeOnClickOutside=false, closeOnMouseOut=false);
    };
    document.body.appendChild(portalButton);

    const portalClickOutsideButton = document.createElement('button');
    portalClickOutsideButton.textContent = 'Open Portal Click Outside';
    portalClickOutsideButton.onclick = (event) => {
        POPUPS.showAsPortal("portal-click-outside", event.clientX, event.clientY, originY="top", originX="left", nudgeOnScreen=true, closeOnClickOutside=true, closeOnMouseOut=false);
    };
    document.body.appendChild(portalClickOutsideButton);

    const portalHoverOutsideButton = document.createElement('button');
    portalHoverOutsideButton.textContent = 'Open Portal Hover Outside';
    portalHoverOutsideButton.onclick = (event) => {
        POPUPS.showAsPortal("portal-hover-outside", event.clientX, event.clientY, originY="top", originX="left", nudgeOnScreen=true, closeOnClickOutside=false, closeOnMouseOut=true);
    };
    document.body.appendChild(portalHoverOutsideButton);

    // Append div with width=100% and margin-bottom=200px to allow scrolling for testing portals
    const spacer = document.createElement('div');
    spacer.style.width = '100%';
    spacer.style.marginBottom = '200px';
    document.body.appendChild(spacer);
});