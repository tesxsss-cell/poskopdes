(() => {
'use strict';
const sidebar = document.getElementById('app-sidebar');
const overlay = document.getElementById('app-sidebar-overlay');
const workspace = document.getElementById('app-workspace');
const shortcuts = document.querySelector('.mobile-shortcuts');
const opener = document.querySelector('[data-sidebar-open]');
const closer = document.querySelector('[data-sidebar-close]');
const desktop = matchMedia('(min-width:1024px)');
let desktopOpen = true;
let mobileOpen = false;
let lastFocus = null;
try { desktopOpen = localStorage.getItem('kopdes-sidebar-desktop') !== 'closed'; } catch (_) {}
function applySidebar(restoreFocus = false) {
 if (!sidebar || !overlay || !opener) return;
 const mobile = !desktop.matches;
 const visible = mobile ? mobileOpen : desktopOpen;
 sidebar.classList.toggle('is-open', mobile && visible);
 document.body.classList.toggle('sidebar-collapsed', !mobile && !visible);
 document.body.classList.toggle('drawer-visible', mobile && visible);
 overlay.hidden = !(mobile && visible);
 if (workspace) workspace.inert = mobile && visible;
 if (shortcuts) shortcuts.inert = mobile && visible;
 sidebar.inert = !visible;
 opener.setAttribute('aria-expanded', String(visible));
 opener.setAttribute('aria-label', visible ? 'Tutup menu' : 'Buka menu');
 if (restoreFocus && !visible) {
  const target = mobile && lastFocus?.isConnected ? lastFocus : opener;
  target.focus();
 }
}
function toggleSidebar(open) {
 if (desktop.matches) {
  desktopOpen = open;
  try { localStorage.setItem('kopdes-sidebar-desktop', open ? 'open' : 'closed'); } catch (_) {}
 } else {
  if (open) lastFocus = document.activeElement;
  mobileOpen = open;
 }
 applySidebar(!open);
 if (open && !desktop.matches) closer?.focus();
}
opener?.addEventListener('click', () => toggleSidebar(desktop.matches ? !desktopOpen : !mobileOpen));
closer?.addEventListener('click', () => toggleSidebar(false));
overlay?.addEventListener('click', () => toggleSidebar(false));
addEventListener('keydown', event => {
 if (!sidebar) return;
 if (event.key === 'Escape' && (!desktop.matches ? mobileOpen : desktopOpen && sidebar.contains(document.activeElement))) {
  toggleSidebar(false); return;
 }
 if (desktop.matches || !mobileOpen || event.key !== 'Tab') return;
 const links = [...sidebar.querySelectorAll('a[href],button,input,select')].filter(el => !el.disabled && el.getClientRects().length);
 const first = links[0], last = links.at(-1);
 if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
 else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
});
desktop.addEventListener('change', () => { mobileOpen = false; applySidebar(); });
applySidebar();
const label=document.getElementById('network-status');
function status(){if(!label)return;label.classList.toggle('is-disconnected',!navigator.onLine);label.lastElementChild.textContent=navigator.onLine?'Online':'Terputus';label.title=navigator.onLine?'Aplikasi memerlukan koneksi ke server.':'Tidak ada mode offline. Transaksi belum dapat dikonfirmasi server.';}
addEventListener('online',status);addEventListener('offline',status);status();
// Pensiunkan SW lama; jangan mendaftarkan PWA atau menghapus antrean historis.
if('serviceWorker' in navigator)navigator.serviceWorker.getRegistrations().then(async registrations=>{
 for(const registration of registrations){const worker=registration.active||registration.waiting||registration.installing;if(worker&&new URL(worker.scriptURL).pathname==='/sw.js'){await registration.update().catch(()=>{});await registration.unregister();}}
}).catch(()=>{});
if('caches' in window)caches.keys().then(keys=>Promise.all(keys.filter(key=>/^(kopdes-|pos-)/.test(key)).map(key=>caches.delete(key)))).catch(()=>{});
})();
