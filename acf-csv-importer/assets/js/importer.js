jQuery(document).ready(function($) {
    'use strict';

    var totalRows = 0;
    var processedRows = 0;

    // שלב 1: טיפול בהעלאת קובץ
    $('#csv-upload-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var securityNonce = $('#csv_upload_nonce_field').val();
        
        formData.append('action', 'acf_csv_importer_upload_csv');
        formData.append('security', securityNonce);

        $.ajax({
            url: acf_csv_importer.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submit').prop('disabled', true).val(acf_csv_importer.i18n.uploading);
            },
            success: function(response) {
                if (response.success) {
                    populateMappingTable(response.data);
                    $('#importer-step-1').hide();
                    $('#importer-step-2').show();
                } else {
                    alert(response.data.message);
                    $('#submit').prop('disabled', false).val('Upload and Continue');
                }
            },
            error: function() {
                alert(acf_csv_importer.i18n.error_processing);
                $('#submit').prop('disabled', false).val('Upload and Continue');
            }
        });
    });

    // פונקציה למילוי טבלת המיפוי
    function populateMappingTable(data) {
        $('#uploaded-file-path').val(data.file_path);
        var tableBody = $('#csv-mapping-table-body');
        tableBody.empty();

        $.each(data.headers, function(index, header) {
            var preview = data.first_row && data.first_row[index] ? data.first_row[index] : '';
            var row = $('<tr>');
            row.append($('<td>').text(header));
            
            var select = $('<select>').attr('name', 'mapping[' + index + '][field_id]');
            select.append($('<option>').val('skip').text('--- התעלם ---'));

            $.each(data.mapping_fields, function(groupKey, group) {
                var optgroup = $('<optgroup>').attr('label', group.label);
                $.each(group.options, function(key, label) {
                    optgroup.append($('<option>').val(key).text(label));
                });
                select.append(optgroup);
            });
            
            row.append($('<td>').append(select));
            row.append($('<td>').text(preview));
            
            // הוספת שדה נסתר לשמירת כותרת ה-CSV
            row.append($('<input>').attr({type: 'hidden', name: 'mapping[' + index + '][csv_header]', value: header}));

            tableBody.append(row);
        });
    }

    // שלב 2: טיפול במיפוי והתחלת ייבוא
    $('#csv-mapping-form').on('submit', function(e) {
        e.preventDefault();

        $('#importer-step-2').hide();
        $('#importer-step-3').show();
        
        prepareImport();
    });
    
    // הכנת הייבוא
    function prepareImport() {
        var mappingData = $('#csv-mapping-form').serializeArray();
        var data = {
            action: 'acf_csv_importer_prepare_import',
            security: acf_csv_importer.nonce,
            file_path: $('#uploaded-file-path').val(),
            post_type: $('#post_type_selector').val(),
            mapping: mappingData.reduce(function(obj, item) {
                // המרת ה-serializeArray למבנה אובייקטים מתאים
                var name = item.name.match(/mapping\[(\d+)\]\[(\w+)\]/);
                if (name) {
                    var index = name[1];
                    var key = name[2];
                    if (!obj[index]) {
                        obj[index] = {};
                    }
                    obj[index][key] = item.value;
                }
                return obj;
            }, [])
        };

        $.post(acf_csv_importer.ajax_url, data, function(response) {
            if (response.success) {
                totalRows = response.data.total_rows;
                if(totalRows > 0) {
                    $('#progress-status').text('נמצאו ' + totalRows + ' שורות לייבוא. מתחיל...');
                    startImport();
                } else {
                    $('#progress-status').text('לא נמצאו שורות לייבוא.');
                    showResults(0, 0, []);
                }
            } else {
                alert(response.data.message);
            }
        });
    }

    // התחלת תהליך הייבוא
    function startImport() {
        processedRows = 0;
        processNextBatch();
    }
    
    // עיבוד אצוות (batch)
    function processNextBatch() {
        if (processedRows >= totalRows) {
            return; // סיום
        }

        var data = {
            action: 'acf_csv_importer_perform_import',
            security: acf_csv_importer.nonce,
        };

        $.post(acf_csv_importer.ajax_url, data, function(response) {
            if (response.success) {
                processedRows = response.data.processed;
                var progress = (processedRows / totalRows) * 100;
                
                $('#progress-bar').css('width', progress + '%');
                $('#progress-status').text('מעבד... ' + processedRows + ' / ' + totalRows);

                if (response.data.done) {
                    showResults(totalRows, processedRows, response.data.errors);
                } else {
                    processNextBatch();
                }
            } else {
                alert(response.data.message || acf_csv_importer.i18n.error_processing);
            }
        });
    }

    // הצגת תוצאות
    function showResults(total, processed, errors) {
        $('#progress-status').text(acf_csv_importer.i18n.import_complete);
        
        var successCount = processed - errors.length;
        var summary = 'הייבוא הושלם. ' + successCount + ' פוסטים נוצרו/עודכנו בהצלחה.';
        if (errors.length > 0) {
            summary += ' נמצאו ' + errors.length + ' שגיאות.';
            $('#error-log-wrapper').show();
            var errorList = $('#error-log');
            errorList.empty();
            $.each(errors, function(i, error) {
                errorList.append($('<li>').text(error));
            });
        }

        $('#results-summary').text(summary);
        $('.import-progress').hide();
        $('#import-results').show();
    }
});
