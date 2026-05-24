<script>
(function () {
    'use strict';

    const cloudName = document.querySelector('meta[name="cloudinary-cloud-name"]')?.content || '';
    const uploadPreset = document.querySelector('meta[name="cloudinary-upload-preset"]')?.content || '';

    function escapeAttr(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function thumbUrl(url, size) {
        if (!url) return '';
        const s = size || 200;
        return url.replace('/upload/', `/upload/c_thumb,w_${s},h_${s},g_auto/`);
    }

    window.openCloudinaryWidget = function (folder, multiple, onSuccess) {
        if (!cloudName || !uploadPreset) {
            alert('Cloudinary no está configurado. Avisale al admin (faltan CLOUDINARY_CLOUD_NAME y/o CLOUDINARY_UPLOAD_PRESET).');
            return;
        }
        if (typeof cloudinary === 'undefined') {
            alert('El widget de Cloudinary no cargó todavía. Refrescá la página.');
            return;
        }

        const widget = cloudinary.createUploadWidget({
            cloudName: cloudName,
            uploadPreset: uploadPreset,
            folder: 'ma-piscinas/' + folder,
            sources: ['local', 'url', 'camera'],
            multiple: !!multiple,
            maxFileSize: 10000000,
            clientAllowedFormats: ['jpg', 'jpeg', 'png', 'webp'],
            language: 'es',
            text: { es: { menu: { files: 'Mis archivos', web: 'Por URL', camera: 'Cámara' } } },
        }, function (error, result) {
            if (error) {
                console.error('Cloudinary widget error:', error);
                return;
            }
            if (result && result.event === 'success' && result.info) {
                onSuccess({ url: result.info.secure_url, public_id: result.info.public_id });
            }
        });
        widget.open();
    };

    window.renderCloudinaryImageInput = function (name, folder, value, label, idSuffix) {
        idSuffix = idSuffix || '';
        const v = value || null;
        const hasValue = v && v.url && v.public_id;
        const containerId = 'cld-img-' + name.replace(/\W+/g, '_') + idSuffix;
        const previewHtml = hasValue
            ? `<img src="${escapeAttr(thumbUrl(v.url, 220))}" alt="Vista previa" style="width:100%;height:100%;object-fit:cover;display:block;">`
            : `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#94a3b8;gap:.4rem;">
                   <i class="bi bi-cloud-arrow-up" style="font-size:2.25rem;"></i>
                   <span class="small">Click para subir imagen</span>
                   <span class="text-muted" style="font-size:.72rem;">JPG · PNG · WEBP · max 10MB</span>
               </div>`;

        return `
            <div class="mb-2" id="${containerId}" data-cld-folder="${escapeAttr(folder)}" data-cld-name="${escapeAttr(name)}" data-cld-type="single">
                <label class="form-label">${escapeAttr(label)}</label>
                <div class="cld-dropzone" data-cloudinary-upload
                     style="width:100%;max-width:220px;aspect-ratio:1/1;border:2px dashed #cbd5e1;border-radius:10px;overflow:hidden;cursor:pointer;background:#f8fafc;transition:border-color .15s,background .15s;position:relative;"
                     onmouseover="this.style.borderColor='var(--primary,#0284c7)';this.style.background='#f0f9ff';"
                     onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc';">
                    <div class="cld-preview" style="width:100%;height:100%;">${previewHtml}</div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-cloudinary-upload>
                        <i class="bi bi-cloud-upload"></i> ${hasValue ? 'Reemplazar' : 'Subir imagen'}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-cloudinary-remove style="display:${hasValue ? 'inline-block' : 'none'};">
                        <i class="bi bi-x-circle"></i> Quitar
                    </button>
                </div>
                <input type="hidden" name="${escapeAttr(name)}[url]" value="${escapeAttr(hasValue ? v.url : '')}" data-cld-field="url">
                <input type="hidden" name="${escapeAttr(name)}[public_id]" value="${escapeAttr(hasValue ? v.public_id : '')}" data-cld-field="public_id">
            </div>
        `;
    };

    window.renderCloudinaryGalleryInput = function (name, folder, value, label, idSuffix) {
        idSuffix = idSuffix || '';
        const items = Array.isArray(value) ? value.filter(i => i && i.url && i.public_id) : [];
        const containerId = 'cld-gal-' + name.replace(/\W+/g, '_') + idSuffix;
        const itemsHtml = items.map((item, idx) => `
            <div class="cld-gal-item" data-idx="${idx}" style="position:relative;display:inline-block;">
                <img src="${escapeAttr(thumbUrl(item.url, 120))}" alt="Imagen ${idx + 1}" style="width:110px;height:110px;object-fit:cover;border-radius:8px;border:1px solid var(--border);display:block;">
                <button type="button" class="btn btn-sm btn-danger" data-cloudinary-gallery-remove
                        style="position:absolute;top:-6px;right:-6px;width:26px;height:26px;padding:0;border-radius:50%;line-height:1;font-size:1rem;"
                        aria-label="Quitar imagen ${idx + 1}">×</button>
                <input type="hidden" name="${escapeAttr(name)}[${idx}][url]" value="${escapeAttr(item.url)}">
                <input type="hidden" name="${escapeAttr(name)}[${idx}][public_id]" value="${escapeAttr(item.public_id)}">
            </div>
        `).join('');

        const addBtnHtml = `
            <div class="cld-gal-add" data-cloudinary-upload
                 style="width:110px;height:110px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;color:#64748b;gap:.25rem;background:#f8fafc;transition:border-color .15s,background .15s;"
                 onmouseover="this.style.borderColor='var(--primary,#0284c7)';this.style.background='#f0f9ff';"
                 onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc';"
                 aria-label="Agregar imagen">
                <i class="bi bi-plus-lg" style="font-size:1.5rem;"></i>
                <span style="font-size:.72rem;">Agregar</span>
            </div>
        `;

        return `
            <div class="mb-2" id="${containerId}" data-cld-folder="${escapeAttr(folder)}" data-cld-name="${escapeAttr(name)}" data-cld-type="gallery">
                <label class="form-label">${escapeAttr(label)}</label>
                <div class="cld-gal-items d-flex flex-wrap gap-2 align-items-center">${itemsHtml}${addBtnHtml}</div>
                <div class="form-text">Podés subir varias imágenes. Click en × para quitar una.</div>
            </div>
        `;
    };

    function reindexGallery(container) {
        const name = container.getAttribute('data-cld-name');
        const items = container.querySelectorAll('.cld-gal-item');
        items.forEach((item, idx) => {
            item.setAttribute('data-idx', idx);
            const inputs = item.querySelectorAll('input[type="hidden"]');
            inputs.forEach(input => {
                const oldName = input.getAttribute('name');
                if (oldName && oldName.endsWith('[url]')) {
                    input.setAttribute('name', `${name}[${idx}][url]`);
                } else if (oldName && oldName.endsWith('[public_id]')) {
                    input.setAttribute('name', `${name}[${idx}][public_id]`);
                }
            });
            const removeBtn = item.querySelector('[data-cloudinary-gallery-remove]');
            if (removeBtn) removeBtn.setAttribute('aria-label', `Quitar imagen ${idx + 1}`);
        });
    }

    function addGalleryItem(container, result) {
        const name = container.getAttribute('data-cld-name');
        const itemsRow = container.querySelector('.cld-gal-items');
        const addBtn = itemsRow.querySelector('.cld-gal-add');
        const idx = itemsRow.querySelectorAll('.cld-gal-item').length;
        const wrapper = document.createElement('div');
        wrapper.className = 'cld-gal-item';
        wrapper.setAttribute('data-idx', idx);
        wrapper.style.cssText = 'position:relative;display:inline-block;';
        wrapper.innerHTML = `
            <img src="${escapeAttr(thumbUrl(result.url, 120))}" alt="Imagen ${idx + 1}" style="width:110px;height:110px;object-fit:cover;border-radius:8px;border:1px solid var(--border);display:block;">
            <button type="button" class="btn btn-sm btn-danger" data-cloudinary-gallery-remove
                    style="position:absolute;top:-6px;right:-6px;width:26px;height:26px;padding:0;border-radius:50%;line-height:1;font-size:1rem;"
                    aria-label="Quitar imagen ${idx + 1}">×</button>
            <input type="hidden" name="${escapeAttr(name)}[${idx}][url]" value="${escapeAttr(result.url)}">
            <input type="hidden" name="${escapeAttr(name)}[${idx}][public_id]" value="${escapeAttr(result.public_id)}">
        `;
        if (addBtn) {
            itemsRow.insertBefore(wrapper, addBtn);
        } else {
            itemsRow.appendChild(wrapper);
        }
    }

    function updateSingleItem(container, result) {
        const url = result ? result.url : '';
        const publicId = result ? result.public_id : '';
        const urlInput = container.querySelector('input[data-cld-field="url"]');
        const idInput = container.querySelector('input[data-cld-field="public_id"]');
        if (urlInput) urlInput.value = url;
        if (idInput) idInput.value = publicId;
        const preview = container.querySelector('.cld-preview');
        if (preview) {
            preview.innerHTML = result
                ? `<img src="${escapeAttr(thumbUrl(url, 220))}" alt="Vista previa" style="width:100%;height:100%;object-fit:cover;display:block;">`
                : `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#94a3b8;gap:.4rem;">
                       <i class="bi bi-cloud-arrow-up" style="font-size:2.25rem;"></i>
                       <span class="small">Click para subir imagen</span>
                       <span class="text-muted" style="font-size:.72rem;">JPG · PNG · WEBP · max 10MB</span>
                   </div>`;
        }
        const uploadBtn = container.querySelector('.d-flex [data-cloudinary-upload]');
        const removeBtn = container.querySelector('[data-cloudinary-remove]');
        if (uploadBtn) uploadBtn.innerHTML = `<i class="bi bi-cloud-upload"></i> ${result ? 'Reemplazar' : 'Subir imagen'}`;
        if (removeBtn) removeBtn.style.display = result ? 'inline-block' : 'none';
    }

    document.addEventListener('click', function (e) {
        const uploadBtn = e.target.closest('[data-cloudinary-upload]');
        if (uploadBtn) {
            e.preventDefault();
            const container = uploadBtn.closest('[data-cld-folder]');
            if (!container) return;
            const folder = container.getAttribute('data-cld-folder');
            const type = container.getAttribute('data-cld-type');
            window.openCloudinaryWidget(folder, false, function (result) {
                if (type === 'gallery') {
                    addGalleryItem(container, result);
                } else {
                    updateSingleItem(container, result);
                }
            });
            return;
        }

        const removeBtn = e.target.closest('[data-cloudinary-remove]');
        if (removeBtn) {
            e.preventDefault();
            const container = removeBtn.closest('[data-cld-folder]');
            if (container) updateSingleItem(container, null);
            return;
        }

        const galRemoveBtn = e.target.closest('[data-cloudinary-gallery-remove]');
        if (galRemoveBtn) {
            e.preventDefault();
            const item = galRemoveBtn.closest('.cld-gal-item');
            const container = galRemoveBtn.closest('[data-cld-folder]');
            if (item) item.remove();
            if (container) reindexGallery(container);
        }
    });
})();
</script>
