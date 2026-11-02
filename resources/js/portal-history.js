export function installPortalHistoryReload(targetWindow) {
    targetWindow.addEventListener('pageshow', (event) => {
        if (event.persisted) targetWindow.location.reload();
    });
}
