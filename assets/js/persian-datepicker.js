/*
 * Persian Datepicker with Time Picker for Mini CRM
 * Requires persian-date.js
 */

(function($, window, undefined) {
    'use strict';

    var PersianDatepicker = function(element, options) {
        this.element = $(element);
        this.options = $.extend({}, PersianDatepicker.DEFAULTS, options);
        this.isOpen = false;
        this.currentDate = PersianDate.getCurrentPersianDate();
        this.selectedDate = null;
        this.selectedTime = '09:00';
        
        this.init();
    };

    PersianDatepicker.DEFAULTS = {
        format: 'YYYY/MM/DD',
        placeholder: 'انتخاب تاریخ',
        minDate: null,
        maxDate: null,
        showTimePicker: true,
        defaultTime: '09:00'
    };

    PersianDatepicker.prototype = {
        
        init: function() {
            this.createWrapper();
            this.bindEvents();
            if (this.element.val()) {
                this.parseValue();
            }
        },

        createWrapper: function() {
            this.wrapper = $('<div class="persian-datepicker-wrapper"></div>');
            this.element.wrap(this.wrapper);
            this.wrapper = this.element.parent();
            
            this.picker = $('<div class="persian-datepicker-container"></div>');
            this.picker.appendTo('body');
            
            this.createCalendar();
            if (this.options.showTimePicker) {
                this.createTimePicker();
            }
        },

        createCalendar: function() {
            var self = this;
            var calendar = $('<div class="persian-calendar"></div>');
            
            // Header
            var header = $('<div class="calendar-header"></div>');
            var prevBtn = $('<button type="button" class="btn-nav btn-prev">‹</button>');
            var nextBtn = $('<button type="button" class="btn-nav btn-next">›</button>');
            var monthYear = $('<div class="month-year"></div>');
            
            // Year selector
            var yearSelect = $('<select class="year-select"></select>');
            this.yearSelect = yearSelect;
            
            header.append(prevBtn).append(monthYear).append(nextBtn);
            calendar.append(header);
            
            // Days header
            var daysHeader = $('<div class="days-header"></div>');
            var dayNames = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
            dayNames.forEach(function(day) {
                daysHeader.append('<div class="day-name">' + day + '</div>');
            });
            calendar.append(daysHeader);
            
            // Days grid
            this.daysGrid = $('<div class="days-grid"></div>');
            calendar.append(this.daysGrid);
            
            this.picker.append(calendar);
            
            // Bind navigation events
            prevBtn.on('click', function() {
                self.navigateMonth(-1);
            });
            
            nextBtn.on('click', function() {
                self.navigateMonth(1);
            });
            
            this.updateCalendar();
        },

        createTimePicker: function() {
            var timePicker = $('<div class="time-picker"></div>');
            var timeLabel = $('<label>ساعت:</label>');
            this.timeInput = $('<input type="time" class="time-input" value="' + this.selectedTime + '">');
            
            timePicker.append(timeLabel).append(this.timeInput);
            this.picker.append(timePicker);
        },

        updateCalendar: function() {
            var self = this;
            var year = this.currentDate.year;
            var month = this.currentDate.month;
            
            // Update header with year selector
            var monthName = PersianDate.getPersianMonthName(month);
            
            // Create year options (10 years range around current year)
            var today = PersianDate.getCurrentPersianDate();
            var currentPersianYear = today.year; // سال جاری شمسی
            var yearOptions = '';
            for (var y = currentPersianYear - 5; y <= currentPersianYear + 10; y++) {
                var persianYear = y.toString().replace(/\d/g, function(d) {
                    return String.fromCharCode(1776 + parseInt(d));
                });
                var selected = (y === year) ? 'selected' : '';
                yearOptions += '<option value="' + y + '" ' + selected + '>' + persianYear + '</option>';
            }
            
            this.picker.find('.month-year').html(
                monthName + ' <select class="year-select" style="background: none; border: none; color: #333; font-size: 14px; font-weight: bold; cursor: pointer; margin-right: 5px;">' + 
                yearOptions + 
                '</select>'
            );
            
            // Clear days grid
            this.daysGrid.empty();
            
            // Get first day of month
            var firstDay = PersianDate.persianToGregorian(year, month, 1);
            var firstDayOfWeek = new Date(firstDay.year, firstDay.month - 1, firstDay.day).getDay();
            
            // Adjust for Persian calendar (Saturday = 0)
            firstDayOfWeek = (firstDayOfWeek + 1) % 7;
            
            // Add empty cells for days before month start
            for (var i = 0; i < firstDayOfWeek; i++) {
                this.daysGrid.append('<div class="day-cell empty"></div>');
            }
            
            // Add month days
            var daysInMonth = PersianDate.getPersianMonthDays(year, month);
            for (var day = 1; day <= daysInMonth; day++) {
                var persianDay = day.toString().replace(/\d/g, function(d) {
                    return String.fromCharCode(1776 + parseInt(d));
                });
                var dayCell = $('<div class="day-cell" data-day="' + day + '">' + persianDay + '</div>');
                
                // Mark today
                var today = PersianDate.getCurrentPersianDate();
                if (year === today.year && month === today.month && day === today.day) {
                    dayCell.addClass('today');
                }
                
                // Mark selected
                if (this.selectedDate && year === this.selectedDate.year && 
                    month === this.selectedDate.month && day === this.selectedDate.day) {
                    dayCell.addClass('selected');
                }
                
                dayCell.on('click', function() {
                    self.selectDate(year, month, parseInt($(this).data('day')));
                });
                
                this.daysGrid.append(dayCell);
            }
        },

        navigateMonth: function(direction) {
            var newMonth = this.currentDate.month + direction;
            var newYear = this.currentDate.year;
            
            if (newMonth > 12) {
                newMonth = 1;
                newYear++;
            } else if (newMonth < 1) {
                newMonth = 12;
                newYear--;
            }
            
            this.currentDate = {year: newYear, month: newMonth, day: 1};
            this.updateCalendar();
        },

        selectDate: function(year, month, day) {
            this.selectedDate = {year: year, month: month, day: day};
            this.updateCalendar();
            this.updateInput();
        },

        updateInput: function() {
            if (this.selectedDate) {
                var dateStr = PersianDate.formatPersianDate(
                    this.selectedDate.year, 
                    this.selectedDate.month, 
                    this.selectedDate.day
                );
                
                if (this.options.showTimePicker && this.timeInput) {
                    this.selectedTime = this.timeInput.val();
                    dateStr += ' ' + this.selectedTime;
                }
                
                this.element.val(dateStr);
                this.element.trigger('change');
            }
        },

        parseValue: function() {
            var value = this.element.val();
            if (value) {
                var parts = value.split(' ');
                var datePart = parts[0];
                var timePart = parts[1] || this.options.defaultTime;
                
                try {
                    this.selectedDate = PersianDate.parsePersianDate(datePart);
                    this.selectedTime = timePart;
                    this.currentDate = {
                        year: this.selectedDate.year,
                        month: this.selectedDate.month,
                        day: this.selectedDate.day
                    };
                    
                    if (this.timeInput) {
                        this.timeInput.val(this.selectedTime);
                    }
                } catch (e) {
                    // Invalid date format
                }
            }
        },

        show: function() {
            if (this.isOpen) return;
            
            var offset = this.element.offset();
            var height = this.element.outerHeight();
            
            this.picker.css({
                position: 'absolute',
                top: offset.top + height + 5,
                left: offset.left,
                zIndex: 9999
            }).show();
            
            this.isOpen = true;
            
            // Close on outside click
            var self = this;
            $(document).on('click.persian-datepicker', function(e) {
                if (!$(e.target).closest('.persian-datepicker-container, .persian-datepicker-wrapper').length) {
                    self.hide();
                }
            });
        },

        hide: function() {
            if (!this.isOpen) return;
            
            this.picker.hide();
            this.isOpen = false;
            $(document).off('click.persian-datepicker');
        },

        bindEvents: function() {
            var self = this;
            
            this.element.on('click focus', function() {
                self.show();
            });
            
            if (this.timeInput) {
                this.timeInput.on('change', function() {
                    self.selectedTime = $(this).val();
                    self.updateInput();
                });
            }
            
            // Year selector change event (delegated event)
            this.picker.on('change', '.year-select', function() {
                var newYear = parseInt($(this).val());
                self.currentDate.year = newYear;
                self.updateCalendar();
            });
        },

        getValue: function() {
            return {
                date: this.selectedDate,
                time: this.selectedTime,
                formatted: this.element.val()
            };
        },

        setValue: function(dateStr) {
            this.element.val(dateStr);
            this.parseValue();
            this.updateCalendar();
        },

        destroy: function() {
            this.picker.remove();
            this.element.unwrap();
            this.element.off('.persian-datepicker');
            $(document).off('click.persian-datepicker');
        }
    };

    // jQuery plugin
    $.fn.persianDatepicker = function(options) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('persian-datepicker');
            
            if (!data) {
                $this.data('persian-datepicker', (data = new PersianDatepicker(this, options)));
            }
            
            if (typeof options === 'string') {
                data[options]();
            }
        });
    };

    // Auto-initialize
    $(document).ready(function() {
        $('.persian-datepicker').persianDatepicker();
    });

})(jQuery, window); 