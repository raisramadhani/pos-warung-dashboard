/**
 * POS UI helpers: notifications (SweetAlert2), modal toggles, fullscreen, darkmode, mobile navigation.
 */

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

export const showToast = (icon, title) => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
};

export const showError = (title, text) => {
    Swal.fire({
        icon: 'error',
        title,
        text,
        confirmButtonColor: '#155dfc',
        confirmButtonText: 'OK',
    });
};

export const showInfo = (title, text) => {
    Swal.fire({
        icon: 'info',
        title,
        text,
        confirmButtonColor: '#155dfc',
        confirmButtonText: 'OK',
    });
};

export const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

export const closeModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

export const applyStockOpnameLock = (isLocked) => {
    if (!isLocked) return;
    openModal('stock-opname-lock-modal');
    const payBtn = document.getElementById('btn-pay');
    if (payBtn) {
        payBtn.disabled = true;
        payBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
};

export const toggleMobileMenu = () => {
    const sidebar = document.getElementById('mobile-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar || !backdrop) return;
    if (sidebar.classList.contains('-translate-x-full')) {
        backdrop.classList.remove('hidden');
        setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
    } else {
        backdrop.classList.add('opacity-0');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        setTimeout(() => backdrop.classList.add('hidden'), 300);
    }
};

export const toggleDarkMode = () => {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
};

export const toggleFullscreen = () => {
    if (!document.fullscreenEnabled) return;
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        document.documentElement.requestFullscreen();
    }
};

export const updateFullscreenIcon = () => {
    const isFullscreen = !!document.fullscreenElement;
    const enterIcon = document.getElementById('icon-fullscreen-enter');
    const exitIcon = document.getElementById('icon-fullscreen-exit');
    if (!enterIcon || !exitIcon) return;
    enterIcon.classList.toggle('hidden', isFullscreen);
    exitIcon.classList.toggle('hidden', !isFullscreen);
};

let pendingSidebarNavUrl = null;

export const confirmSidebarNav = (event, url) => {
    event.preventDefault();
    pendingSidebarNavUrl = url;
    openModal('sidebar-nav-confirm-modal');
    return false;
};

export const confirmSidebarNavProceed = () => {
    closeModal('sidebar-nav-confirm-modal');
    if (pendingSidebarNavUrl) {
        window.location.href = pendingSidebarNavUrl;
    }
};
