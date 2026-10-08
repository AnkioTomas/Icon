window.pageLoadFiles = ['Captcha'];
window.pageOnLoad = function () {
    const MAX_BYTES = 5 * 1024 * 1024;
    let file = null;
    let objectUrl = '';

    const $dropZone = $('#dropZone');
    const radiusSlider = $('#radius')[0];

    function renderPreview() {
        const bg = $('#bgColor').val();
        const radius = radiusSlider.value;
        $('#radiusValue').text(radius);
        $('#previewRow .icon').each(function () {
            const size = $(this).attr('data-size') + 'px';
            this.style.width = size;
            this.style.height = size;
            this.style.borderRadius = ($(this).attr('data-radius') || radius) + '%';
            this.style.backgroundColor = $(this).attr('data-kind') === 'opaque' ? bg : 'transparent';
            this.style.backgroundImage = objectUrl ? `url(${objectUrl})` : 'none';
            this.style.outline = objectUrl ? 'none' : '1px dashed rgb(var(--mdui-color-outline))';
        });
    }

    function pick(picked) {
        if (!picked) {
            return;
        }
        if (!picked.type.startsWith('image/')) {
            $.toaster.error('请选择图片文件');
            return;
        }
        if (picked.size > MAX_BYTES) {
            $.toaster.error('图片不能超过 5MB');
            return;
        }
        file = picked;
        URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(picked);
        $('#sourcePreview').attr('src', objectUrl).removeAttr('hidden');
        $('#uploadIcon').css('display', 'none');
        $('#uploadText').text(picked.name);
        renderPreview();
    }

    function submit(platforms, sizes, captcha) {
        const btn = $('#generateBtn')[0];
        const form = new FormData();
        form.append('image', file);
        form.append('platforms', platforms.join(','));
        form.append('sizes', sizes);
        form.append('bg', $('#bgColor').val());
        form.append('radius', radiusSlider.value);
        form.append('captcha', captcha);

        btn.loading = true;
        // $.request 只支持 urlencoded/JSON，上传文件必须用 FormData
        fetch('/generate', { method: 'POST', body: form })
            .then((resp) => resp.json())
            .then((res) => {
                if (res.code !== 200) {
                    $.toaster.error(res.msg);
                    return;
                }
                $.toaster.success('生成成功，开始下载');
                window.location.href = res.data.url;
            })
            .catch(() => $.toaster.error('生成失败，请稍后重试'))
            .finally(() => {
                btn.loading = false;
            });
    }

    const fileInput = $('#imageInput')[0];
    $(fileInput).on('change', function () {
        pick(this.files[0]);
        // 清空后再次选同一文件才会触发 change
        this.value = '';
    });
    $dropZone
        .on('click', () => fileInput.click())
        .on('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        })
        .on('dragover', (e) => {
            e.preventDefault();
            $dropZone.addClass('dragging');
        })
        .on('dragleave', () => $dropZone.removeClass('dragging'))
        .on('drop', (e) => {
            e.preventDefault();
            $dropZone.removeClass('dragging');
            pick(e.dataTransfer.files[0]);
        });

    $('#bgColor').on('input', renderPreview);
    $(radiusSlider).on('input', renderPreview);

    $('#generateBtn').on('click', function () {
        if (!file) {
            $.toaster.error('请先上传图片');
            return;
        }
        const platforms = $('#platformChips mdui-chip').get().filter((chip) => chip.selected).map((chip) => chip.value);
        const sizes = $('#customSizes')[0].value.trim();
        if (platforms.length === 0 && sizes === '') {
            $.toaster.error('请至少选择一个平台或填写自定义尺寸');
            return;
        }
        $('#captcha')[0].show((result) => {
            if (result) {
                submit(platforms, sizes, result.captcha);
            }
        });
    });

    window.pageOnUnLoad = function () {
        URL.revokeObjectURL(objectUrl);
    };

    renderPreview();
    return false;
};
