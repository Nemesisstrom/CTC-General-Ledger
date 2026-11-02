export function restoreSidebarScroll() {
    const nav = document.getElementById('sidebar-nav-container');
    const savedScroll = sessionStorage.getItem('ctc_sidebar_scroll');

    if (nav && savedScroll) {
        nav.scrollTop = savedScroll;
    }
}
