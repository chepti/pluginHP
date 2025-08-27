/**
 * Homer Patuach Annual Timeline Admin JavaScript
 * Handles admin interface functionality
 */

(function($) {
    'use strict';

    class HPAT_Admin {
        constructor() {
            this.bindEvents();
        }

        bindEvents() {
            // Create timeline button
            $(document).on('click', '#hpat-create-timeline', this.showCreateTimelineModal.bind(this));

            // Edit timeline buttons
            $(document).on('click', '.hpat-edit-timeline', this.editTimeline.bind(this));

            // Modal events
            $(document).on('click', '.hpat-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.hpat-modal-backdrop', this.closeModal.bind(this));
            $(document).on('click', '.hpat-modal-content', function(e) {
                e.stopPropagation();
            });

            // Form submissions
            $(document).on('submit', '#hpat-create-timeline-form', this.createTimeline.bind(this));
            $(document).on('submit', '#hpat-edit-timeline-form', this.updateTimeline.bind(this));

            // Delete confirmation
            $(document).on('click', '.hpat-delete-timeline', this.confirmDelete.bind(this));
        }

        showCreateTimelineModal() {
            const modal = this.createModal('צור ציר זמן חדש', this.getCreateTimelineForm());
            $('body').append(modal);
            modal.fadeIn(300);
        }

        getCreateTimelineForm() {
            return `
                <form id="hpat-create-timeline-form">
                    <div class="hpat-form-group">
                        <label for="timeline_group_id">מזהה קבוצת לימוד:</label>
                        <input type="text" id="timeline_group_id" name="group_id" required
                               placeholder="לדוגמה: math_7th_grade">
                        <p class="description">מזהה ייחודי לקבוצת הלימוד (אותיות באנגלית וקווים תחתונים בלבד)</p>
                    </div>

                    <div class="hpat-form-group">
                        <label for="timeline_group_name">שם קבוצת הלימוד:</label>
                        <input type="text" id="timeline_group_name" name="group_name" required
                               placeholder="לדוגמה: מתמטיקה - כיתה ז'">
                    </div>

                    <div class="hpat-form-group">
                        <label for="timeline_academic_year">שנת לימוד:</label>
                        <select id="timeline_academic_year" name="academic_year" required>
                            ${this.generateAcademicYearOptions()}
                        </select>
                    </div>

                    <div class="hpat-modal-footer">
                        <button type="button" class="hpat-button-secondary hpat-modal-close">ביטול</button>
                        <button type="submit" class="hpat-button-primary">צור ציר זמן</button>
                    </div>

                    <input type="hidden" name="action" value="hpat_create_timeline">
                    <input type="hidden" name="nonce" value="${hpat_admin_ajax.nonce}">
                </form>
            `;
        }

        generateAcademicYearOptions() {
            const currentYear = new Date().getFullYear();
            let options = '';

            for (let i = -1; i <= 2; i++) {
                const year = currentYear + i;
                const academicYear = `${year}-${year + 1}`;
                const selected = (i === 0) ? 'selected' : '';
                options += `<option value="${academicYear}" ${selected}>${academicYear}</option>`;
            }

            return options;
        }

        createTimeline(e) {
            e.preventDefault();

            const form = $(e.target);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.text();

            // Disable button and show loading
            submitButton.prop('disabled', true).html('<div class="hpat-spinner"></div> יוצר...');

            const formData = new FormData(form[0]);

            $.ajax({
                url: hpat_admin_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message || 'הציר הזמן נוצר בהצלחה');
                        this.closeModal();
                        location.reload(); // Reload to show new timeline
                    } else {
                        this.showError(response.data.message || hpat_admin_ajax.strings.error);
                    }
                },
                error: () => {
                    this.showError(hpat_admin_ajax.strings.error);
                },
                complete: () => {
                    submitButton.prop('disabled', false).text(originalText);
                }
            });
        }

        editTimeline(e) {
            e.preventDefault();

            const timelineId = $(e.target).data('timeline-id');

            $.ajax({
                url: hpat_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hpat_get_timeline_data',
                    timeline_id: timelineId,
                    nonce: hpat_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const modal = this.createModal('ערוך ציר זמן', this.getEditTimelineForm(response.data));
                        $('body').append(modal);
                        modal.fadeIn(300);
                    } else {
                        this.showError(response.data.message || hpat_admin_ajax.strings.error);
                    }
                },
                error: () => {
                    this.showError(hpat_admin_ajax.strings.error);
                }
            });
        }

        getEditTimelineForm(timelineData) {
            return `
                <form id="hpat-edit-timeline-form">
                    <div class="hpat-form-group">
                        <label for="edit_timeline_group_id">מזהה קבוצת לימוד:</label>
                        <input type="text" id="edit_timeline_group_id" name="group_id"
                               value="${timelineData.group_id}" readonly>
                        <p class="description">לא ניתן לשנות את מזהה הקבוצה</p>
                    </div>

                    <div class="hpat-form-group">
                        <label for="edit_timeline_group_name">שם קבוצת הלימוד:</label>
                        <input type="text" id="edit_timeline_group_name" name="group_name"
                               value="${timelineData.group_name}" required>
                    </div>

                    <div class="hpat-form-group">
                        <label for="edit_timeline_academic_year">שנת לימוד:</label>
                        <select id="edit_timeline_academic_year" name="academic_year" required>
                            ${this.generateAcademicYearOptions(timelineData.academic_year)}
                        </select>
                    </div>

                    <div class="hpat-modal-footer">
                        <button type="button" class="hpat-button-secondary hpat-modal-close">ביטול</button>
                        <button type="submit" class="hpat-button-primary">שמור שינויים</button>
                    </div>

                    <input type="hidden" name="action" value="hpat_update_timeline">
                    <input type="hidden" name="timeline_id" value="${timelineData.id}">
                    <input type="hidden" name="nonce" value="${hpat_admin_ajax.nonce}">
                </form>
            `;
        }

        updateTimeline(e) {
            e.preventDefault();

            const form = $(e.target);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.text();

            // Disable button and show loading
            submitButton.prop('disabled', true).html('<div class="hpat-spinner"></div> שומר...');

            const formData = new FormData(form[0]);

            $.ajax({
                url: hpat_admin_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message || 'הציר הזמן עודכן בהצלחה');
                        this.closeModal();
                        location.reload(); // Reload to show changes
                    } else {
                        this.showError(response.data.message || hpat_admin_ajax.strings.error);
                    }
                },
                error: () => {
                    this.showError(hpat_admin_ajax.strings.error);
                },
                complete: () => {
                    submitButton.prop('disabled', false).text(originalText);
                }
            });
        }

        confirmDelete(e) {
            e.preventDefault();

            const timelineId = $(e.target).data('timeline-id');
            const timelineName = $(e.target).data('timeline-name');

            if (confirm(`${hpat_admin_ajax.strings.confirm_delete} "${timelineName}"?`)) {
                this.deleteTimeline(timelineId);
            }
        }

        deleteTimeline(timelineId) {
            $.ajax({
                url: hpat_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hpat_delete_timeline',
                    timeline_id: timelineId,
                    nonce: hpat_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message || 'הציר הזמן נמחק בהצלחה');
                        location.reload(); // Reload to update list
                    } else {
                        this.showError(response.data.message || hpat_admin_ajax.strings.error);
                    }
                },
                error: () => {
                    this.showError(hpat_admin_ajax.strings.error);
                }
            });
        }

        createModal(title, content) {
            return $(`
                <div class="hpat-modal-backdrop">
                    <div class="hpat-modal-content">
                        <div class="hpat-modal-header">
                            <h2>${title}</h2>
                            <button type="button" class="hpat-modal-close">&times;</button>
                        </div>
                        <div class="hpat-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
        }

        closeModal() {
            $('.hpat-modal-backdrop').fadeOut(300, function() {
                $(this).remove();
            });
        }

        showError(message) {
            this.showMessage(message, 'error');
        }

        showSuccess(message) {
            this.showMessage(message, 'success');
        }

        showMessage(message, type) {
            // Remove existing messages
            $('.hpat-notice').remove();

            // Create new message
            const notice = $(`<div class="hpat-notice hpat-notice-${type}">${message}</div>`);

            // Add to page
            $('.wp-header-end').after(notice);

            // Animate in
            notice.hide().slideDown(300);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notice.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Initialize admin functionality when document is ready
    $(document).ready(function() {
        if (typeof hpat_admin_ajax !== 'undefined') {
            new HPAT_Admin();
        }
    });

})(jQuery);
