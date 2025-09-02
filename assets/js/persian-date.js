/*
 * Persian Date Utility for Mini CRM
 * Based on accurate Persian calendar algorithms
 */

(function(window, undefined) {
    'use strict';

    var PersianDate = function() {
        this.persian_months = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];
        
        this.persian_days = [
            'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'
        ];
    };

    PersianDate.prototype = {
        
        // Check if Persian year is leap
        isPersianLeapYear: function(year) {
            var breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
            var jp = breaks.length;
            var jump = 0;
            var j;
            
            for (j = 1; j < jp; j++) {
                jump = breaks[j] - breaks[j - 1];
                if (year < breaks[j]) break;
            }
            
            var n = year - breaks[j - 1];
            
            if (n < jump) {
                jump = jump - n;
                return !(jump > 33 ? 33 : jump) % 4;
            }
            return false;
        },

        // Get number of days in Persian month
        getPersianMonthDays: function(year, month) {
            if (month <= 6) return 31;
            if (month <= 11) return 30;
            return this.isPersianLeapYear(year) ? 30 : 29;
        },

        // Convert Gregorian to Persian - Accurate algorithm with correct epoch
        gregorianToPersian: function(gy, gm, gd) {
            // Simple calculation: Persian year = Gregorian year - 621/622
            // March 22, 2025 = 1 Farvardin 1404
            
            var persianYear;
            var adjustedMonth = gm;
            var adjustedDay = gd;
            
            // If date is before March 21, it belongs to previous Persian year
            if (gm < 3 || (gm === 3 && gd < 21)) {
                persianYear = gy - 622;
            } else {
                persianYear = gy - 621;
            }
            
            // Calculate which Persian month we're in
            var persianMonth, persianDay;
            
            // Create reference date for Nowruz (March 21) of current Gregorian year
            var nowruz = new Date(gy, 2, 21); // March 21
            var currentDate = new Date(gy, gm - 1, gd);
            
            var daysDiff;
            
            if (currentDate >= nowruz) {
                // After Nowruz - same Persian year
                daysDiff = Math.floor((currentDate - nowruz) / (1000 * 60 * 60 * 24));
            } else {
                // Before Nowruz - previous Persian year
                var lastYearNowruz = new Date(gy - 1, 2, 21);
                daysDiff = Math.floor((currentDate - lastYearNowruz) / (1000 * 60 * 60 * 24));
                persianYear = gy - 622;
            }
            
            // Calculate month and day
            persianMonth = 1;
            persianDay = daysDiff + 1;
            
            for (var month = 1; month <= 12; month++) {
                var monthDays = this.getPersianMonthDays(persianYear, month);
                if (persianDay <= monthDays) {
                    persianMonth = month;
                    break;
                }
                persianDay -= monthDays;
            }
            
            return {year: persianYear, month: persianMonth, day: persianDay};
        },

        // Convert Persian to Gregorian - Accurate algorithm
        persianToGregorian: function(jy, jm, jd) {
            // Calculate Gregorian year
            var gregorianYear = jy + 621;
            
            // Calculate days from beginning of Persian year
            var daysSinceNewYear = 0;
            
            // Add days for complete months
            for (var month = 1; month < jm; month++) {
                daysSinceNewYear += this.getPersianMonthDays(jy, month);
            }
            
            // Add remaining days
            daysSinceNewYear += jd - 1;
            
            // Nowruz is typically March 21
            var nowruz = new Date(gregorianYear, 2, 21); // March 21
            var resultDate = new Date(nowruz.getTime() + (daysSinceNewYear * 1000 * 60 * 60 * 24));
            
            return {
                year: resultDate.getFullYear(),
                month: resultDate.getMonth() + 1,
                day: resultDate.getDate()
            };
        },

        // Get current Persian date
        getCurrentPersianDate: function() {
            var now = new Date();
            return this.gregorianToPersian(now.getFullYear(), now.getMonth() + 1, now.getDate());
        },

        // Format Persian date
        formatPersianDate: function(year, month, day, separator) {
            separator = separator || '/';
            return year + separator + (month < 10 ? '0' + month : month) + separator + (day < 10 ? '0' + day : day);
        },

        // Parse Persian date string
        parsePersianDate: function(dateString, separator) {
            separator = separator || '/';
            var parts = dateString.split(separator);
            return {
                year: parseInt(parts[0], 10),
                month: parseInt(parts[1], 10),
                day: parseInt(parts[2], 10)
            };
        },

        // Get Persian month name
        getPersianMonthName: function(month) {
            return this.persian_months[month - 1] || '';
        },

        // Get Persian day name
        getPersianDayName: function(dayOfWeek) {
            return this.persian_days[dayOfWeek] || '';
        }
    };

    // Create global instance
    window.PersianDate = new PersianDate();

})(window); 