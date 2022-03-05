Validation.add('validate-phone-numberonly-atleast7', 'Please enter a valid phone number. Minimum 7 numbers and no character.', function (v) {
    v = v.replace(/ /g, "");
    return Validation.get('IsEmpty').test(v) || /^([0-9]{7,})$/i.test(v);
});
Validation.add('validate-number-only', 'Please use numbers only in this field!', function (v) {
    v = v.replace(/ /g, "");
    return Validation.get('IsEmpty').test(v) || /^([0-9]*)$/i.test(v);
});
Validation.add('validate-length-minimum-100', 'Please enter at minimum 100 character!', function (v) {
    v = v.trim();
    return Validation.get('IsEmpty').test(v) || (v.length >= 100);
});
Validation.add('validate-billing-ctelephone', 'Please make sure your telephone match.', function (v) {
    var conf = $$('.validate-billing-telephone')[0];
    return (v.trim() == conf.value.trim());
});
Validation.add('validate-shipping-ctelephone', 'Please make sure your telephone match.', function (v) {
    var conf = $$('.validate-shipping-telephone')[0];
    return (v.trim() == conf.value.trim());
});
Validation.add('validate-maximum-length-100', 'Please enter at maximum 100 character!', function (v) {
    v = v.trim();
    return Validation.get('IsEmpty').test(v) || (v.length <= 100);
});
