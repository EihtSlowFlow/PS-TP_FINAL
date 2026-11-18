/*
 * Photos of the property form (views/properties/form_images.php).
 *
 * - Shows a preview of the chosen photos.
 * - Warns when they do not fit in the free slots (the server checks it again).
 * - Shrinks big photos (more than 1600 px per side or more than 1.5 MB) to a JPEG before sending them, so a phone
 *   photo fits in PHP's default upload limit (2 MB). The server re-encodes every photo anyway, so without this script
 *   the form keeps working: only a very heavy photo would be rejected with a clear message.
 */
(function () {
    'use strict';

    var MAX_SIDE = 1600;
    var SHRINK_ABOVE_BYTES = 1.5 * 1024 * 1024;
    var JPEG_QUALITY = 0.85;

    var input = document.getElementById('property-images');
    var preview = document.getElementById('property-images-preview');
    var submit = document.getElementById('btn-save-property');

    if (!input || !preview) {
        return;
    }

    var maxFiles = parseInt(input.getAttribute('data-max-files') || '8', 10);
    var removeBoxes = document.querySelectorAll('input[name="remove_images[]"]');

    function freeSlots() {
        var removing = 0;
        for (var i = 0; i < removeBoxes.length; i++) {
            if (removeBoxes[i].checked) {
                removing++;
            }
        }
        return maxFiles + removing;
    }

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                resolve({ img: img, url: url });
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('not an image'));
            };
            img.src = url;
        });
    }

    function shrink(file) {
        return loadImage(file).then(function (loaded) {
            var img = loaded.img;
            var width = img.naturalWidth;
            var height = img.naturalHeight;
            var scale = Math.min(1, MAX_SIDE / Math.max(width, height));

            if (scale === 1 && file.size <= SHRINK_ABOVE_BYTES) {
                URL.revokeObjectURL(loaded.url);
                return file;
            }

            var canvas = document.createElement('canvas');
            canvas.width = Math.round(width * scale);
            canvas.height = Math.round(height * scale);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(loaded.url);

            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', JPEG_QUALITY);
            });
        }).catch(function () {
            return file;
        });
    }

    function renderPreview(files, message) {
        preview.innerHTML = '';

        if (message) {
            var note = document.createElement('li');
            note.className = 'property-image-preview-note';
            note.setAttribute('role', 'alert');
            note.textContent = message;
            preview.appendChild(note);
        }

        for (var i = 0; i < files.length; i++) {
            var item = document.createElement('li');
            var thumb = document.createElement('img');
            thumb.src = URL.createObjectURL(files[i]);
            thumb.alt = 'Foto nueva ' + (i + 1);
            thumb.onload = function () {
                URL.revokeObjectURL(this.src);
            };
            var caption = document.createElement('span');
            caption.textContent = (files[i].size / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
            item.appendChild(thumb);
            item.appendChild(caption);
            preview.appendChild(item);
        }

        preview.hidden = files.length === 0 && !message;
    }

    input.addEventListener('change', function () {
        var chosen = Array.prototype.slice.call(input.files || []);
        var slots = freeSlots();
        var message = '';

        if (chosen.length > slots) {
            message = 'Elegiste ' + chosen.length + ' fotos y queda lugar para ' + slots
                + '. Se van a enviar solo las primeras ' + slots + '.';
            chosen = chosen.slice(0, slots);
        }

        if (submit) {
            submit.disabled = true;
        }

        Promise.all(chosen.map(shrink)).then(function (files) {
            try {
                var transfer = new DataTransfer();
                files.forEach(function (file) {
                    transfer.items.add(file);
                });
                input.files = transfer.files;
            } catch (e) {
                // Without DataTransfer the original files are sent; the server still validates and shrinks them.
                files = chosen;
            }
            renderPreview(files, message);
        }).finally(function () {
            if (submit) {
                submit.disabled = false;
            }
        });
    });
}());
