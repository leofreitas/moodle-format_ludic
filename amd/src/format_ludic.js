// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main js file of format_ludic
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Javascript functions for ludic course format.

define(['jquery', 'jqueryui'], function ($, ui) {
    let courseId = null;
    let userId = null;
    let editMode = null;
    let ludic = {

        /**
         * Always called in a format_ludic page
         * Initialize all required events.
         * @param {object} params
         */
        init: function (params) {
            // Defines some useful variables.
            ludic.courseId = params.courseid;
            ludic.userId = params.userid;
            ludic.editMode = params.editmode;

            // Initialize all required events.
            ludic.initEvents();

            // If we are in edit mode, show sections after loading the page.
            if (ludic.editMode) {
                ludic.displaySections();
            }

            // Click on last item clicked in order to keep navigation.
            ludic.clickOnTheLastItemClicked();
        },

        /**
         * Initialize all the general events to be monitored at startup in this function.
         */
        initEvents: function () {
            console.log('initEvents');
            let body = $('body.format-ludic');

            // Always init popup events.
            ludic.initPopupEvents();

            // Save the element selector of the last clicked event.
            ludic.initSaveLastItemClickedEvents();

            // For each element with ludic-action class.
            // Makes an ajax call to the controller defined in data-controller with the action defined in data-action.
            // Then call a callback function defined in data-callback.
            body.on('click', '.ludic-action', function () {
                let callback = $(this).data('callback');
                let controller = $(this).data('controller');
                let action = $(this).data('action');
                console.log('click on ludic action callback => ', callback, ' controller => ', controller, ' action => ', action);
                if (controller && action) {
                    ludic.ajaxCall({
                        controller: controller,
                        action: action,
                        callback: function (params) {
                            if (callback) {
                                ludic.callFunction(callback, params);
                            } else {
                                console.log('no callback');
                            }
                        }
                    });
                }
            });

            // When you click on an item in .container-parents, call the getChildren() function on the controller of the same type.
            // Then display the return in .container-children.
            body.on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, getChildren');
                let container = $(this).closest('.container-items');
                let id = $(this).data('id');
                let type = $(this).data('type');
                if (!id || !type) {
                    return false;
                }
                let children = container.find('.container-children');
                ludic.addLoading(children);
                ludic.ajaxCall({
                    id: id,
                    controller: type,
                    action: 'get_children',
                    callback: function (html) {
                        if (!html) {
                            return false;
                        }
                        children.html(html);
                        // TODO click en plus sur dernier cm.
                    }
                });
            });

            // If we are in edit mode, initialize related events.
            if (ludic.editMode) {
                ludic.initEditModeEvents();
            }
        },

        /**
         * Initialize all events specific to the edit mode to be monitored at startup in this function.
         */
        initEditModeEvents: function () {
            console.log('initEditmode');

            let body = $('body.format-ludic');

            // Always init drag and drop popup events in edit mode.
            ludic.initDragAndDropEvents();

            // When you click on an item in .container-parents, call the getProperties() function on the controller of the same type.
            // Then display the return in .container-properties.
            body.on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, getProperties');
                let item = $(this);
                let container = item.closest('.container-items');
                container.find('.item.selected').removeClass('selected');
                item.addClass('selected');
                let id = item.data('id');
                let type = item.data('type');
                if (!id || !type) {
                    return false;
                }
                let content = container.find('.container-properties .container-content');
                ludic.addLoading(content);
                ludic.ajaxCall({
                    id: id,
                    controller: type,
                    action: 'get_properties',
                    callback: function (html) {
                        if (!html) {
                            return false;
                        }
                        content.html(html);
                        ludic.initFilepickerComponent(container);
                        ludic.initModchooserComponent(container);
                    }
                });
            });

            // Submit button in a selection popup.
            // Add a input hidden with selected value.
            // Update overview image with selected image.
            body.on('click', '.selection-submit', function () {
                console.log('click on selection submit');
                // Find popup.
                let popup = $(this).closest('.format-ludic.ludic-popup');

                // Find selected item to get img and value.
                let selectedItem = popup.find('.item.selected');
                let selectedImg = selectedItem.find('.item-img-container').html();
                let selectedValue = selectedItem.data('id');

                // Find input and update value.
                let inputId = $(this).data('inputid');
                $(inputId).attr('value', selectedValue);

                // Find img in overview and update value.
                let overView = $(inputId + '-overview .overview-img-container');
                overView.html(selectedImg);

                // Data required for DOMNodeInserted event.
                let container = $('#ludic-main-container');
                let popupId = popup.attr('id');

                // Be sure to have only one active event.
                container.unbind('DOMNodeInserted');

                // Add event to auto select selected-item when reopening a popup.
                container.on('DOMNodeInserted', function (e) {
                    if (e.target.id && e.target.id === popupId) {
                        $('#' + popupId + ' .item[data-id="' + selectedValue + '"]').addClass('selected');
                    }
                });

                // Trigger click on close button to close popup.
                popup.find('.close-ludic-popup').click();
            });

            // Revert form content by clicking in related item.
            body.on('click', '.ludic-button[data-identifier="form-revert"]', function () {
                // Find the item linked to the form and click on it to reset the form.
                let itemType = $(this).data('itemtype');
                let itemId = $(this).data('itemid');
                if (!itemType || !itemId) {
                    return false;
                }
                let itemSelectorId = '.item.' + itemType + '[data-id="' + itemId + '"]';
                $(itemSelectorId).click();
            });

            // Submit form in ajax.
            // Validate and update if all is fine.
            body.on('click', '.ludic-button[data-identifier="form-save"]', function () {
                let itemType = $(this).data('itemtype');
                let itemId = $(this).data('itemid');
                if (!itemType || !itemId) {
                    return false;
                }
                let form = $('#ludic-form-' + itemType + '-' + itemId);
                let serialize = form.serializeArray();
                let container = form.parent().find('.container-success');
                ludic.addLoading(container);
                ludic.ajaxCall({
                    controller: itemType,
                    id: itemId,
                    data: serialize,
                    dataType: 'json',
                    action: 'validate_form',
                    callback: function (json) {
                        console.log('form is validate', json);
                        let newClass = json.success ? 'success' : 'error';
                        let unwantedClass = json.success ? 'error' : 'success';
                        container.html(json.value);
                        container.removeClass(unwantedClass);
                        container.addClass(newClass);

                        // Refresh the updated elements - updateFunction ex : displaySections.
                        let updateFunction = 'display' + itemType.charAt(0).toUpperCase() + itemType.slice(1) + 's';
                        let params = {
                            callback: function () {
                                $('.item.' + itemType + '[data-id="' + itemId + '"]').addClass('selected');
                            }
                        };
                        ludic.callFunction(updateFunction, params);
                    }
                });
            });

            // Show sub buttons.
            body.on('click', '.ludic-button.show-sub-buttons', function () {
                let identifier = $(this).data('identifier');
                $(this).removeClass('show-sub-buttons');
                $(this).addClass('hide-sub-buttons');
                let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');
                subButtons.outerWidth($(this).outerWidth());
                subButtons.css('top', $(this).position().top + $(this).outerHeight());
                subButtons.css('left', $(this).position().left);
                subButtons.removeClass('hide');
            });

            // Hide sub buttons.
            body.on('click', '.ludic-button.hide-sub-buttons', function () {
                let identifier = $(this).data('identifier');
                $(this).removeClass('hide-sub-buttons');
                $(this).addClass('show-sub-buttons');
                let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');
                subButtons.addClass('hide');
            });

            // Redirect to the link in button.
            body.on('click', '.ludic-button[data-link]', function () {
                window.location.href = $(this).data('link');
            });
        },

        /**
         * Initialize all events specific to the display of popups to be monitored at startup in this function.
         */
        initPopupEvents: function () {
            let body = $('body.format-ludic');

            // Add a background for the display of popup.
            body.prepend('<div id="ludic-background"></div>');

            // Close ludic popup.
            body.on('click', '.close-ludic-popup', function () {
                let popup = $(this).closest('.format-ludic.ludic-popup');
                $('#ludic-background').hide();
                $(popup).remove();
            });
        },

        /**
         * Save the last item clicked in sessionStorage.
         */
        initSaveLastItemClickedEvents: function () {
            // Use this variable as an indicator to know if we have already saved the last click.
            let lastTimeStamp = 0;

            // Save the selector of the last element clicked by the user in the .container-parents.
            $('#ludic-main-container .container-parents').on('click', '*', function (e) {

                // If it's not a real click, ignore it.
                if (!e.hasOwnProperty('originalEvent')) {
                    return;
                }

                // Compare the event timestamp with the last timestamp setted.
                let eventTimeStamp = e.timeStamp;
                if (lastTimeStamp !== eventTimeStamp) {
                    // If the last timestamp is different of the event timestamp, save the event timestamp as last timestamp.
                    // We can continue and save the last click.
                    lastTimeStamp = eventTimeStamp;
                } else {
                    // If the last timestamp is equal to the event timestamp, this means that we already save the last click, so return.
                    return;
                }

                // Defines some useful variables.
                let id = $(this).attr('id');
                let selector = '';

                // If there is not consistent id, save the DOM tree selector.
                if (!id || id.indexOf('yui_') === 0) {

                    // Return the list of classes to select it.
                    let getClassListSelector = function (classList) {
                        return classList ? "." + $.trim(classList).replace(/\s/gi, ".") : '';
                    };

                    // Set indicator to know if we are in a ludic course tree.
                    let inLudicContainer = true;

                    // For each parent we add its selector if it is part of the ludic course tree.
                    $(this).parents().each(function () {

                        // Check if we are in a ludic course tree.
                        if ($(this).attr('id') === 'ludic-main-container') {
                            // From now on we are no longer in a ludic course tree.
                            inLudicContainer = false;
                        }

                        // Not in ludic course tree, continue.
                        if (!inLudicContainer) {
                            return;
                        }

                        // If it exists, set the id selector.
                        let currentId = $(this).attr("id");
                        let selectorId = currentId && currentId.indexOf('yui_') === -1 ? '#' + currentId : '';

                        // Composes the selector of the current element.
                        let currentSelector = ' > ' + this.tagName + selectorId + getClassListSelector($(this).attr("class"));

                        // Add it to selector.
                        selector = currentSelector + selector;
                    });

                    // Remove first ' > '.
                    selector = selector.substring(3);

                } else {

                    // Element has id attribute, so save it.
                    let selector = '#' + id;

                }

                // Ensure that selector is not empty.
                if (selector) {

                    // Set last click selector in session storage.
                    sessionStorage.setItem('lastClick', selector);

                }

            });
        },

        /**
         *  Click on the last item clicked.
         */
        clickOnTheLastItemClicked: function () {
            // Retrieve selector of the last item clicked.
            let lastCLick = sessionStorage.getItem('lastClick');

            // If there is not item, there is nothing to do.
            if (!lastCLick) {
                return;
            }

            // Ensure that the item is in page before clicking on it.
            let interval = setInterval(function () {
                let lastItemClicked = $(lastCLick);
                if (lastItemClicked.length > 0) {
                    lastItemClicked.click();
                    clearInterval(interval);
                } else {
                    console.log('wait item ', lastCLick, ' is ready');
                }
            }, 500);
        },

        /**
         * Initialize all drag and drop specific events to monitor.
         */
        initDragAndDropEvents: function () {
            let body = $('body.format-ludic');

            // Save the selector id of the drag object.
            body.on('dragstart', '.ludic-drag', function (e) {
                console.log('dragstart');
                console.log(e.currentTarget.id);
                e.originalEvent.dataTransfer.setData('text/plain', e.currentTarget.id);
            });

            // Required to allow drop.
            body.on('dragover', '.ludic-drop', function (e) {
                console.log('dragover');
                e.preventDefault();
            });

            // Management of drop sections and course modules.
            body.on('drop', '.section.ludic-drop, .coursemodule.ludic-drop', function (e) {
                console.log('drop');
                console.log(e.currentTarget.id);

                // Get drag item data.
                let dragItem = $('#' + e.originalEvent.dataTransfer.getData('text/plain'));
                let dragId = dragItem.data('id');
                let dragType = dragItem.data('type');

                // Define the parent of the dragged object here to receive the html of the callback return.
                let dragParent = dragItem.parent();

                // Get drop item data.
                let dropItem = $('#' + e.currentTarget.id);
                let dropId = dropItem.data('id');
                let dropType = dropItem.data('type');

                if (dragItem.is(dropItem)) {
                    console.log('drop on same item, nothing to do');
                    return false;
                }

                // Define the action here.
                let action = false;
                if (dragType === 'section' && dropType === 'section') {
                    action = 'move_section_to';
                } else if (dragType === 'coursemodule' && dropType === 'section') {
                    action = 'move_to_section';
                } else if (dragType === 'coursemodule' && dropType === 'coursemodule') {
                    action = 'move_on_section';
                }

                // If an action has been found, make an ajax call to the section controller.
                // Then set the html on the parent of the dragged object.
                if (action) {
                    console.log('execute ', action);
                    ludic.ajaxCall({
                        idtomove: dragId,
                        toid: dropId,
                        controller: 'section',
                        action: action,
                        callback: function (html) {
                            dragParent.html(html);
                            ludic.initModchooserComponent(dragParent);
                        }
                    });
                }
            });
        },

        /**
         * Initialize the filepicker component.
         * @param {object} container jQuery element - where are filepickers
         */
        initFilepickerComponent: function (container) {

            // Search filepicker in container, if there is none, there is nothing to do.
            let filepickers = container.find('.container-properties .ludic-filepicker-container');
            if (filepickers.length === 0) {
                return;
            }

            // Initialize each filepicker, with his options.
            filepickers.each(function () {
                console.log('init_filepicker');
                let options = $(this).data('options');
                M.form_filepicker.init(Y, options);

                // Hide options.
                $(this).removeAttr('data-options');
            });

        },

        /**
         * Initialize the modchooser component.
         * @param {object} container jQuery element - where is the modchooser
         */
        initModchooserComponent: function (container) {

            // Search modchooser in container, if there is none, there is nothing to do.
            let modChooser = container.find('.ludic-modchooser');
            if (modChooser.length === 0) {
                return;
            }

            // Defines some useful variables.
            let modChooserConfig = {
                courseid: ludic.courseid,
                closeButtonTitle: undefined
            };

            // Ensure that moodle function which init modchooser is ready before using it.
            let interval = setInterval(function () {
                if (typeof M.course.init_chooser === 'function') {
                    M.course.init_chooser(modChooserConfig);
                    modChooser.show();
                    clearInterval(interval);
                }
            }, 1000);
        },

        /**
         * Send an ajax request
         * @param {object} params
         */
        ajaxCall: function (params) {
            console.log('ajax call ', params);
            let that = this;

            // Constant params.
            params.courseid = ludic.courseId;
            params.userid = ludic.userId;

            // Check optional params.
            let dataType = params.dataType ? params.dataType : 'html';
            let method = params.method ? params.method : 'GET';
            let url = params.url ? params.url : M.cfg.wwwroot + '/course/format/ludic/ajax/ajax.php';
            let async = params.async ? params.async : true;
            let callback = params.callback ? params.callback : null;
            let callbackError = params.error ? params.error : null;

            // Delete params to not send them in the request.
            delete params.dataType;
            delete params.method;
            delete params.url;
            delete params.async;
            delete params.callback;

            // Execute ajax call with good params.
            $.ajax({
                method: method,
                url: url,
                data: params,
                dataType: dataType,
                async: async,
                error: function (jqXHR, error, errorThrown) {
                    if (typeof callbackError === 'function') {
                        callbackError(jqXHR, error, errorThrown);
                    } else if ((jqXHR.responseText.length > 0) && (jqXHR.responseText.indexOf('pagelayout-login') !== -1)) {
                        that.redirectLogin();
                    } else {
                        that.displayErrorPopup();
                    }
                }
            }).done(function (response) {
                if ((response.length > 0) && (response.indexOf('pagelayout-login') !== -1)) {
                    that.redirectLogin();
                }

                if (typeof callback === 'function') {
                    callback(response);
                }
            });
        },

        /**
         * This function allows you to call another function dynamically with parameters.
         * @param {string} name
         * @param {object} || {json} params
         * @returns {boolean|void}
         */
        callFunction: function (name, params = {}) {
            // Ensures that params is an object.
            params = typeof params === "object" ? params : JSON.parse(params);
            console.log('call ', name, ' with params ', params);

            // Define all possible parameters here.
            let html = params.html ? params.html : null;
            let callback = params.callback ? params.callback : null;

            let result = false;
            // Call the right function with the right parameters.
            switch (name) {
                case 'displaySections' :
                    result = ludic.displaySections(callback);
                    break;
                case 'displayPopup':
                    result = ludic.displayPopup(html);
                    break;
                default:
                    return result;
            }

            return result;
        },

        /**
         * Redirect to login page.
         */
        redirectLogin: function () {
            window.location.href = M.cfg.wwwroot + '/login/index.php';
        },

        /**
         * Display popup.
         * @param {string} html - full html of the popup.
         */
        displayPopup: function (html) {
            let selectorId = '#' + $(html).attr('id');
            $(selectorId).remove();
            console.log(selectorId);

            $('#ludic-background').show();
            $('#ludic-main-container').prepend(html);
        },

        /**
         * Display sections in ajax.
         */
        displaySections: function (callback) {
            let container = $('.container-items .container-parents');
            ludic.addLoading(container);
            ludic.ajaxCall({
                controller: 'section',
                action: 'get_parents',
                callback: function (html) {
                    container.html(html);
                    if (typeof callback === 'function') {
                        callback(html);
                    }
                }
            });
        },

        /**
         * Add a loading div
         * @param {object} parent jquery object
         */
        addLoading: function (parent) {
            parent.html('<div class="loading"></div>');
        },
    };
    return ludic;
});