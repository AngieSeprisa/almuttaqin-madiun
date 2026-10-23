/**
 * Upload foto berkas dengan kompresi di sisi browser.
 *
 * Alur: pilih/seret foto -> di-resize (sisi terpanjang maks. 1600 px) -> di-encode JPEG
 * kualitas 72% via <canvas> -> file hasil kompresi menggantikan file asli pada <input>,
 * sehingga yang dikirim ke server hanya versi kecil (biasanya 100-400 KB).
 * Server tetap mengompres ulang sebagai pengaman.
 */
(function () {
    'use strict';

    var MAX_DIMENSION = 1600;
    var JPEG_QUALITY = 0.72;
    var MAX_RAW_BYTES = 25 * 1024 * 1024; // batas wajar file mentah sebelum dikompresi
    var ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    var form = document.getElementById('formPendaftaran');
    var pending = [];

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function loadBitmap(file) {
        // createImageBitmap + 'from-image' memperbaiki orientasi EXIF foto HP.
        if (window.createImageBitmap) {
            return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () {
                return loadViaImage(file);
            });
        }
        return loadViaImage(file);
    }

    function loadViaImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                resolve(img);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Gambar tidak dapat dibaca.'));
            };
            img.src = url;
        });
    }

    function compress(file) {
        return loadBitmap(file).then(function (bitmap) {
            var width = bitmap.width || bitmap.naturalWidth;
            var height = bitmap.height || bitmap.naturalHeight;
            var scale = Math.min(1, MAX_DIMENSION / Math.max(width, height));
            var w = Math.max(1, Math.round(width * scale));
            var h = Math.max(1, Math.round(height * scale));

            var canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; // PNG transparan -> latar putih
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(bitmap, 0, 0, w, h);
            if (bitmap.close) bitmap.close();

            return new Promise(function (resolve, reject) {
                canvas.toBlob(function (blob) {
                    if (!blob) return reject(new Error('Kompresi gagal.'));

                    // Bila file asli sudah lebih kecil & tidak perlu di-resize, pakai asli.
                    if (scale === 1 && file.type === 'image/jpeg' && blob.size >= file.size) {
                        return resolve(file);
                    }
                    var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', JPEG_QUALITY);
            });
        });
    }

    function setInputFile(input, file) {
        var dt = new DataTransfer();
        if (file) dt.items.add(file);
        input.files = dt.files;
    }

    function initCard(card) {
        var input = card.querySelector('[data-berkas-file]');
        var check = card.querySelector('[data-berkas-check]');
        var drop = card.querySelector('[data-upload-drop]');
        var preview = card.querySelector('[data-upload-preview]');
        var img = card.querySelector('[data-upload-img]');
        var nameEl = card.querySelector('[data-upload-name]');
        var statsEl = card.querySelector('[data-upload-stats]');
        var removeBtn = card.querySelector('[data-upload-remove]');
        var errorEl = card.querySelector('[data-upload-error]');
        var objectUrl = null;

        function showError(message) {
            errorEl.textContent = message;
            errorEl.hidden = !message;
        }

        function reset() {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            setInputFile(input, null);
            img.removeAttribute('src');
            preview.hidden = true;
            drop.hidden = false;
            card.classList.remove('has-file', 'is-busy');
        }

        function handle(file) {
            showError('');
            if (!file) return reset();

            if (ALLOWED.indexOf(file.type) === -1) {
                reset();
                return showError('Format tidak didukung. Gunakan JPG, PNG, atau WEBP.');
            }
            if (file.size > MAX_RAW_BYTES) {
                reset();
                return showError('File terlalu besar (maks. 25 MB).');
            }

            card.classList.add('is-busy');
            drop.hidden = true;
            preview.hidden = false;
            nameEl.textContent = file.name;
            statsEl.textContent = 'Mengompres foto…';

            var job = compress(file)
                .then(function (result) {
                    setInputFile(input, result);

                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = URL.createObjectURL(result);
                    img.src = objectUrl;
                    nameEl.textContent = result.name;

                    var saved = Math.max(0, Math.round((1 - result.size / file.size) * 100));
                    statsEl.textContent = formatBytes(file.size) + ' → ' + formatBytes(result.size) +
                        (saved > 0 ? ' (hemat ' + saved + '%)' : '');

                    card.classList.remove('is-busy');
                    card.classList.add('has-file');
                    if (check) check.checked = true;
                })
                .catch(function () {
                    reset();
                    showError('Foto tidak dapat diproses. Coba format JPG/PNG lain.');
                })
                .then(function () {
                    pending = pending.filter(function (p) { return p !== job; });
                });

            pending.push(job);
        }

        input.addEventListener('change', function () {
            handle(input.files && input.files[0]);
        });

        removeBtn.addEventListener('click', function () {
            showError('');
            reset();
        });

        ['dragenter', 'dragover'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                drop.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            drop.addEventListener(evt, function () {
                drop.classList.remove('is-dragover');
            });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (file) handle(file);
        });
    }

    document.querySelectorAll('[data-berkas-card]').forEach(initCard);

    // Cegah submit selagi kompresi masih berjalan; lanjutkan otomatis setelah selesai.
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!pending.length) return;
            e.preventDefault();
            Promise.all(pending).then(function () {
                form.requestSubmit();
            });
        });
    }

    // Cegah browser membuka foto bila terlepas di luar area drop.
    ['dragover', 'drop'].forEach(function (evt) {
        window.addEventListener(evt, function (e) {
            if (!e.target.closest || !e.target.closest('[data-upload-drop]')) e.preventDefault();
        });
    });
})();
