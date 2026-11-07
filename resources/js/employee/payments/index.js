(function () {
            'use strict';

            const keys = [
                'q',
                'payment_type',
                'payment_status_id',
                'payment_method',
                'date_from',
                'date_to'
            ];

            const formId = 'f08-payable-filter';

            /*
             * URL là nguồn dữ liệu chính thức
             * của trạng thái bộ lọc đã áp dụng.
             *
             * Không dùng giá trị form được browser
             * tự phục hồi để suy ra dữ liệu danh sách.
             */
            function syncFromUrl() {
                const form = document.getElementById(formId);

                if (!form) {
                    return;
                }

                const search = new URLSearchParams(
                    window.location.search
                );

                for (const key of keys) {
                    const field = form.elements.namedItem(key);

                    if (!field) {
                        continue;
                    }

                    const requested = search.get(key) || '';

                    if (field.tagName === 'SELECT') {
                        const optionExists = Array.from(
                            field.options
                        ).some(function (option) {
                            return option.value === requested;
                        });

                        field.value = optionExists
                            ? requested
                            : '';
                    } else {
                        field.value = requested;
                    }
                }
            }

            document.addEventListener(
                'DOMContentLoaded',
                syncFromUrl
            );

            /*
             * Khi quay lại từ trang chi tiết hoặc form
             * thu tiền, trình duyệt có thể phục hồi
             * HTML cũ từ Back/Forward Cache.
             *
             * Tải lại dữ liệu từ server để tránh
             * form và danh sách không đồng bộ.
             */
            window.addEventListener(
                'pageshow',
                function (event) {
                    if (event.persisted) {
                        window.location.reload();
                        return;
                    }

                    syncFromUrl();

                    window.requestAnimationFrame(
                        syncFromUrl
                    );
                }
            );

            window.addEventListener(
                'popstate',
                function () {
                    window.location.reload();
                }
            );

            if (document.readyState !== 'loading') {
                syncFromUrl();
            }
        })();
