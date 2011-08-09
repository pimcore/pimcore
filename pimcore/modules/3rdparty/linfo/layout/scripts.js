/**
 * This file is part of Linfo (c) 2010 Joseph Gillotti.
 * 
 * Linfo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Linfo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.  If not, see <http://www.gnu.org/licenses/>.
 * 
*/

/**
 * Manages all Linfo javascript
 * @author Lee Bradley (elephanthunter)
 * 
 * Goals:
 *  - Keep the global scope squeaky clean (and, as a direct result, compression efficient)
 *  - Keep performance blazing fast
 */
window['Linfo'] = (function() {
	/**
	 * Set a cookie key/value pair
	 * @param key
	 * @param value
	 */
	function setCookie(key, value) {
		document.cookie = [
			encodeURIComponent(key), '=',
			encodeURIComponent(value)
		].join('')
	}

	/**
	 * Get the cookie value from the key
	 * @param key
	 * @return the cookie value
	 */
	function getCookie(key) {
		var strEncodedKey = encodeURIComponent(key),
			regex = new RegExp('(?:^|; )' + strEncodedKey + '=([^;]*)'),
			aResult = regex.exec(document.cookie);
		return aResult ? decodeURIComponent(aResult[1]) : null;
	}

	/**
	 * Set the opacity of an element
	 * @param el the element to set
	 * @param opacity the opacity to set it to (0.0 - 1.0)
	 */
	function setOpacity(el, opacity) {
		el.style.opacity = opacity;

		// IE / Windows
		el.style.filter = "alpha(opacity=" + (opacity * 100) + ")";
	}

	/**
	 * Call a function repeatedly for a specified duration
	 * @param fn the function to call
	 * @param timeout when to quit
	 * @param fnComplete the function to call when finished (optional)
	 */
	function callCountdown(fn, timeout, fnComplete) {
		var interval = 10,
			time = 0,
			iFinishTime = timeout - (timeout % interval),
			fnCallback = function() {
				var iPercentage = (time++ * interval) / iFinishTime;
				fn(iPercentage);
				if (iPercentage >= 1) {
					if (fnComplete) fnComplete();
					return;
				}
				setTimeout(fnCallback, interval);
			};

		fnCallback();
	}

	/**
	 * Slide an element to the specified height
	 * @param el the element to slide
	 * @param iEndHeight the end height
	 * @param fnCallback the function to call when finished (optional)
	 * @param time the duration of the animation (optional)
	 */
	function slideTo(el, iEndHeight, fnCallback, time) {
		var iStartHeight = el.offsetHeight,
			iHeightDiff = iStartHeight - iEndHeight;

		callCountdown(
			function(i) {
				var iCurrentHeight = ((1 - i) * iHeightDiff) + iEndHeight;
				el.style.height = iCurrentHeight.toString() + 'px';
			},
			time || 100,
			fnCallback
		);
	};

	/**
	 * Fade an element in
	 * @param el the element to fade
	 * @param fnCallback the function to call when finished (optional)
	 * @param time the duration of the animation (optional)
	 */
	function fadeIn(el, fnCallback, time) {
		callCountdown(
			function(i) { setOpacity(el, i); },
			time || 100,
			fnCallback
		);
	};
	
	/**
	 * Fade an element out
	 * @param el the element to fade
	 * @param fnCallback the function to call when finished (optional)
	 * @param time the duration of the animation (optional)
	 */
	function fadeOut(el, fnCallback, time) {
		callCountdown(
			function(i) { setOpacity(el, 1 - i); },
			time || 200,
			fnCallback
		);
	};

	/**
	 * Check to see if the element has the specified class
	 * @param el
	 * @param strClass
	 * @return true if the element has the class; otherwise false
	 */
	function hasClass(el, strClass) {
		return el.className.match(new RegExp('(\\s|^)' + strClass + '(\\s|$)'));
	}

	/**
	 * Add a class to an element
	 * @param el
	 * @param strClass
	 */
	function addClass(el, strClass) {
		if (!hasClass(el, strClass)) el.className += " " + strClass;
	}

	/**
	 * Remove a class from an element
	 * @param el
	 * @param strClass
	 */
	function removeClass(el, strClass) {
		if (hasClass(el,strClass)) {
			var reg = new RegExp('(\\s|^)' + strClass + '(\\s|$)');
			el.className = el.className.replace(reg,' ');
		}
	}

	/**
	 * Loop through each element in an array, calling the specified function
	 * @param a the array to loop through
	 * @param fn the function to call
	 */
	function each(a, fn) {
		for (var i = 0; i < a.length; i++) {
			fn(i, a[i]);
		}
	}

	/**
	 * Represents a graphical "Section" of data
	 * ex: Core, Memory, etc...
	 */
	var Section = function(elSection) {
		var m_elToggler, m_elTable;

		/**
		 * Save the collapse state
		 * @param bCollapsed true if the section is collapsed
		 */
		function setCollapseState(bCollapsed) {
			setCookie(elSection.id, bCollapsed ? '0' : '1');
		}

		/**
		 * Load the collapse state
		 * @return true if the section is collapsed, otherwise false
		 */
		function getCollapseState() {
			return (getCookie(elSection.id) == '0') ? true : false;
		}

		/**
		 * Collapse the section with animation
		 */
		function collapseAnimated() {
			setCollapseState(true);
			m_elToggler.innerHTML = "+";

			// Fade out, then slide up
			fadeOut(m_elTable, function() {
				elSection.fullSize = elSection.offsetHeight;
				slideTo(elSection, elSection.offsetHeight - m_elTable.offsetHeight, function() {
					elSection.sliding = false;
				});
				addClass(elSection, 'collapsed');
			});
		}

		/**
		 * Expand the section with animation
		 */
		function expandAnimated() {
			setCollapseState(false);
			removeClass(elSection, 'collapsed');
			m_elToggler.innerHTML = "-";

			// Slide down, then fade in
			slideTo(elSection, elSection.fullSize, function() {
				elSection.style.height = "";
				fadeIn(m_elTable, function() {
					elSection.sliding = false;
				});
			});
		}

		/**
		 * Toggle the display of a collapsable Linfo bar
		 */
		function toggleShow() {
			// Make sure we're not on already sliding
			if (elSection.sliding) return;
			elSection.sliding = true;

			if (hasClass(elSection, 'collapsed')) {
				expandAnimated();
			} else {
				collapseAnimated();
			}
		}

		/**
		 * Collapse a section instantly
		 */
		function collapse() {
			var iNewHeight = elSection.offsetHeight - m_elTable.offsetHeight; 

			m_elToggler.innerHTML = "+";
			setOpacity(m_elTable, 0);

			elSection.fullSize = elSection.offsetHeight;
			elSection.style.height = iNewHeight.toString() + 'px';
			addClass(elSection, 'collapsed');
		}

		/**
		 * Create a toggler for the specified section
		 * @param elSection
		 */
		function createToggler() {
			// Create a new toggler
			m_elToggler = document.createElement('span');
			m_elToggler.className = 'toggler';
			m_elToggler.onclick = toggleShow;
			m_elToggler.innerHTML = '-';

			// Put the toggler at the top of the element
			elSection.insertBefore(m_elToggler, elSection.firstChild);
		}

		/**
		 * Set the section id from the section's title
		 * @param elSection
		 */
		function generateIdFromTitle() {
			// Get the title
			var strTitle = elSection.getElementsByTagName('h2')[0].innerHTML;

			// Clean up the title and set it to the div's id
			elSection.id = strTitle.split(' ').join('_').toLowerCase();
		}

		/**
		 * Initialize the section
		 */
		function init() {
			createToggler();
			generateIdFromTitle();

			// Get the information table
			m_elTable = elSection.getElementsByTagName('table')[0];

			if (getCollapseState()) {
				collapse();
			}
		}

		init();
	};

	/**
	 * Initialize the sections
	 */
	function initializeSections() {
		// Get a list of divs
		var aDivs = document.getElementsByTagName('div');

		// Loop through them all
		each(aDivs, function(i, elSection) {
			// If this is an infoTable
			if (hasClass(elSection, 'infoTable')) {
				new Section(elSection);
			}
		});
	}

	/**
	 * Initialize Linfo. Called on dom ready
	 */
	function init() {
		initializeSections();
	}

	return {
		'init': init
	};
}());
