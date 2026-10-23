const displayTime = (value) => {
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}:\d{2}))?$/);
    if (match) return `${match[3]}/${match[2]}/${match[1]}${match[4] ? ` lúc ${match[4]}` : ''}`;
    const month = String(value).match(/^(\d{4})-(\d{2})$/);
    return month ? `${month[2]}/${month[1]}` : value;
};

export function validationMessage(control, label = 'Thông tin') {
    const validity = control.validity;
    if (validity.badInput) return `Vui lòng nhập giá trị hợp lệ cho “${label}”${control.type === 'number' ? ', chỉ dùng chữ số và dấu thập phân nếu được phép' : ''}.`;
    if (validity.typeMismatch) {
        if (control.type === 'email') return 'Địa chỉ email chưa đúng. Ví dụ địa chỉ hợp lệ: ten@example.com.';
        if (control.type === 'url') return 'Liên kết chưa đúng. Vui lòng nhập địa chỉ bắt đầu bằng http:// hoặc https://.';
        return `Nội dung ở ô “${label}” chưa đúng định dạng. Vui lòng kiểm tra và nhập lại.`;
    }
    if (validity.valueMissing) {
        if (control.type === 'checkbox') return 'Vui lòng đánh dấu ô này trước khi tiếp tục.';
        if (control.tagName === 'SELECT' || control.type === 'radio') return `Vui lòng chọn ${label.toLowerCase()}.`;
        if (control.type === 'file') return `Vui lòng chọn tệp cho mục “${label}”.`;
        return `Vui lòng nhập hoặc chọn ${label.toLowerCase()}.`;
    }
    const temporal = ['date', 'time', 'datetime-local', 'month'].includes(control.type);
    if (validity.rangeUnderflow) return temporal
        ? `${label} không được trước ${displayTime(control.min)}. Vui lòng chọn lại.`
        : `${label} không được nhỏ hơn ${control.min}.`;
    if (validity.rangeOverflow) return temporal
        ? `${label} không được sau ${displayTime(control.max)}. Vui lòng chọn lại.`
        : `${label} không được vượt quá ${control.max}.`;
    if (validity.stepMismatch) {
        if (control.name === 'price') return 'Giá dịch vụ phải chia hết cho 1.000 đồng, ví dụ 0, 1000 hoặc 2000.';
        if (temporal) return `Vui lòng chọn lại “${label}” bằng công cụ trong ô nhập.`;
        const base = Number(control.min || control.getAttribute?.('value') || 0);
        if (control.step === '1' && Number.isInteger(base)) return `${label} phải là số nguyên, không nhập phần thập phân.`;
        const decimalStep = String(control.step).match(/^0\.(0*)1$/);
        if (decimalStep && Number.isInteger(base * (10 ** (decimalStep[1].length + 1)))) {
            return `${label} chỉ được có tối đa ${decimalStep[1].length + 1} chữ số sau dấu thập phân.`;
        }
        return `Vui lòng nhập lại “${label}”. Ví dụ giá trị hợp lệ: ${base}.`;
    }
    if (validity.patternMismatch) {
        if (control.name === 'phone') return 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0, ví dụ 0912345678.';
        if (control.name === 'citizen_id_number') return 'Số căn cước công dân phải gồm đúng 12 chữ số.';
        return `Nội dung ở ô “${label}” chưa đúng định dạng. Vui lòng kiểm tra hướng dẫn và nhập lại.`;
    }
    if (validity.tooLong) return `${label} chỉ được nhập tối đa ${control.maxLength} ký tự.`;
    if (validity.tooShort) return `${label} cần ít nhất ${control.minLength} ký tự.`;

    return '';
}
