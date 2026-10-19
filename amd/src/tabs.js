// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Accessible tabs of the "Your course in numbers" page (Feature 008, contracts/ui.md).
 *
 * Progressive enhancement: without this module both areas are shown one after the other and the list of tabs
 * stays hidden. With it, the WAI-ARIA tabs pattern applies (arrows, Home and End with automatic activation), each
 * tab has an address (#block_coursecare-tab-...), browser history restores the tab, and an address of a section
 * opens the tab that holds it and moves the focus to the section.
 *
 * @module     block_coursecare/tabs
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The area and the element named by the address of the page, when they belong to these tabs.
 *
 * @param {HTMLElement[]} panels The areas.
 * @returns {{index: number, target: HTMLElement}|null}
 */
const fromAddress = (panels) => {
    const id = decodeURIComponent(window.location.hash.slice(1));
    const target = id ? document.getElementById(id) : null;
    if (!target) {
        return null;
    }
    const index = panels.findIndex((panel) => panel.contains(target));
    return index < 0 ? null : {index, target};
};

/**
 * Move the focus to an element that is not focusable by itself, without leaving it in the tab order.
 *
 * @param {HTMLElement} element The element.
 */
const focusElement = (element) => {
    if (!element.hasAttribute('tabindex')) {
        element.setAttribute('tabindex', '-1');
        element.addEventListener('blur', () => element.removeAttribute('tabindex'), {once: true});
    }
    element.focus();
};

/**
 * Turn the two areas of the page into tabs.
 *
 * @param {string} selector Selector of the page container (data-region="block_coursecare-overview").
 */
export const init = (selector) => {
    const root = document.querySelector(selector);
    const tablist = root ? root.querySelector('[data-region="block_coursecare-tablist"]') : null;
    if (!tablist) {
        return;
    }
    const tabs = Array.from(tablist.querySelectorAll('[data-tab]'));
    const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
    if (panels.includes(null)) {
        return;
    }

    tablist.setAttribute('role', 'tablist');
    tabs.forEach((tab, index) => {
        tab.setAttribute('role', 'tab');
        panels[index].setAttribute('role', 'tabpanel');
        panels[index].setAttribute('aria-labelledby', tab.id);
        // The name of the tab already announces the area: its heading stays for assistive technologies only.
        const heading = panels[index].querySelector('.block_coursecare-overview-panelheading');
        if (heading) {
            heading.classList.add('sr-only');
        }
    });

    const initial = Math.max(0, tabs.findIndex((tab) => tab.dataset.tab === root.dataset.initialtab));

    const select = (index) => {
        tabs.forEach((tab, i) => {
            const active = i === index;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
            panels[i].hidden = !active;
        });
    };

    const restore = () => {
        const found = fromAddress(panels);
        if (found) {
            select(found.index);
            focusElement(found.target);
        } else {
            select(initial);
        }
    };

    const activate = (index, moveFocus) => {
        select(index);
        if (moveFocus) {
            tabs[index].focus();
        }
        const address = '#' + panels[index].id;
        if (window.location.hash !== address) {
            window.history.pushState(null, '', address);
        }
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(index, false));
    });
    tablist.addEventListener('keydown', (event) => {
        const current = tabs.indexOf(document.activeElement);
        if (current < 0) {
            return;
        }
        const last = tabs.length - 1;
        const next = {
            ArrowRight: current === last ? 0 : current + 1,
            ArrowLeft: current === 0 ? last : current - 1,
            Home: 0,
            End: last,
        }[event.key];
        if (next === undefined) {
            return;
        }
        event.preventDefault();
        activate(next, true);
    });
    window.addEventListener('popstate', restore);
    window.addEventListener('hashchange', restore);

    root.classList.add('block_coursecare-overview-hastabs');
    tablist.hidden = false;
    restore();
};
