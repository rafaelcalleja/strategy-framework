(function ($) {
    $.fn.extend({
        pschecker: function (options) {
            var settings = $.extend({ minlength: 8, maxlength: 16, onPasswordValidate: null, onPasswordMatch: null }, options);
            return this.each(function () {
                var wrapper = $('.password-container');
                var password = $('.strong-password:eq(0)', wrapper);
                var cPassword = $('.strong-password:eq(1)', wrapper);

                cPassword.removeClass('no-match');
                password.keyup(validatePassword).blur(validatePassword).focus(validatePassword);
                cPassword.keyup(validatePassword).blur(validatePassword).focus(validatePassword);

                function validatePassword() {
                    var pstr = password.val().toString();
                    var meter = $('.meter-container');
                    var debil = settings.debil;
                    var aceptable = settings.aceptable;
                    var fuerte = settings.fuerte;
                    meter.html("");
                    //fires password validate event if password meets the min length requirement
                    if (settings.onPasswordValidate != null)
                        settings.onPasswordValidate(pstr.length >= settings.minlength);

                    if (pstr.length < settings.maxlength)
                        meter.removeClass('strong').removeClass('medium').removeClass('week');

                    if (pstr.length > 0) {
                        var alpha = containsAlpha(pstr),
                            number = containsNumeric(pstr),
                            upper = containsUpperCase(pstr),
                            special = containsSpecialCharacter(pstr);

                        if (alpha && number && upper && special && pstr.length > 7) {
                            meter.addClass('strong');
                            meter.html(fuerte);
                        }
                        else if (alpha && number && upper && pstr.length > 7) {
                            meter.addClass('medium');
                            meter.html(aceptable);
                        }
                        else {
                            meter.addClass('week');
                            meter.html(debil);
                        }

                        if (cPassword.val().toString().length > 0) {
                            if (pstr == cPassword.val().toString()) {
                                cPassword.removeClass('no-match');
                                if (settings.onPasswordMatch != null)
                                    settings.onPasswordMatch(true);
                            }
                            else {
                                cPassword.addClass('no-match');
                                if (settings.onPasswordMatch != null)
                                    settings.onPasswordMatch(false);
                            }
                        }
                        else {
                            cPassword.addClass('no-match');
                            if (settings.onPasswordMatch != null)
                                settings.onPasswordMatch(false);
                        }
                    }
                }

                function containsAlpha(str) {
                    var rx = new RegExp(/[a-z]/);
                    if (rx.test(str)) return 1;
                    return 0;
                }

                function containsNumeric(str) {
                    var rx = new RegExp(/[0-9]/);
                    if (rx.test(str)) return 1;
                    return 0;
                }

                function containsUpperCase(str) {
                    var rx = new RegExp(/[A-Z]/);
                    if (rx.test(str)) return 1;
                    return 0;
                }

                function containsSpecialCharacter(str) {
                    var rx = new RegExp(/[\W]/);
                    var rxe = new RegExp(/[ ]/);
                    if (rxe.test(str)) {return 0}
                        else {
                            if (rx.test(str)) return 1;
                            return 0;
                        }
                }


            });
        }
    });
})(jQuery);
