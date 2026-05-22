// AI Tutorial Generator - Job Status Tracker
// Polls backend API for job progress updates

define(['jquery', 'core/notification', 'core/str'], function($, Notification, Str) {
    
    /**
     * Initialize job tracker
     * @param {number} cmid Course module ID
     */
    function init(cmid) {
        console.log('AI Tutorial Generator: Job tracker initialized for CMID:', cmid);
        
        // Poll for job status updates every 5 seconds.
        setInterval(function() {
            pollJobStatus(cmid);
        }, 5000);
    }
    
    /**
     * Poll backend for job status
     * @param {number} cmid Course module ID
     */
    function pollJobStatus(cmid) {
        var jobCards = $('.aitutorial-job-card');
        
        jobCards.each(function() {
            var jobCard = $(this);
            var jobid = jobCard.data('jobid');
            var badge = jobCard.find('.badge');
            
            // Only poll for processing jobs.
            if (!badge.hasClass('status-processing')) {
                return;
            }
            
            $.ajax({
                url: M.cfg.wwwroot + '/mod/aitutorial/api/status.php',
                type: 'GET',
                data: { 
                    jobid: jobid,
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        console.error('Error polling job', jobid, response.error);
                        return;
                    }
                    
                    updateJobCard(jobCard, response);
                },
                error: function(xhr, status, error) {
                    console.error('Failed to poll job', jobid, error);
                }
            });
        });
    }
    
    /**
     * Update job card UI with new status
     * @param {jQuery} jobCard Job card element
     * @param {Object} data Job status data
     */
    function updateJobCard(jobCard, data) {
        var badge = jobCard.find('.badge');
        
        // Update progress bar.
        if (data.progress !== undefined) {
            var progressBar = jobCard.find('.progress-bar');
            var progressText = jobCard.find('.progress-text');
            
            if (progressBar.length > 0) {
                progressBar.css('width', data.progress + '%');
                progressBar.attr('aria-valuenow', data.progress);
            }
            
            if (progressText.length > 0) {
                progressText.text(data.progress + '% complete');
            }
        }
        
        // Update status badge.
        if (data.status && data.status !== 'processing') {
            badge.removeClass('status-processing')
               .addClass('status-' + data.status)
               .text(Str.get_string('status_' + data.status, 'mod_aitutorial'));
            
            // Reload page if job completed or failed.
            if (data.status === 'completed' || data.status === 'failed') {
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }
    }
    
    // Retry job generation
    $(document).on('click', '.btn-retry', function() {
        var jobid = $(this).data('jobid');
        
        if (!confirm('Are you sure you want to retry this generation?')) {
            return;
        }
        
        $.ajax({
            url: M.cfg.wwwroot + '/mod/aitutorial/api/retry.php',
            type: 'POST',
            data: { 
                jobid: jobid,
                sesskey: M.cfg.sesskey
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Notification.success('Retry initiated', 'Regeneration started. This page will refresh.');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    Notification.exception(response.error);
                }
            },
            error: function(xhr, status, error) {
                Notification.exception('Failed to retry job');
            }
        });
    });
    
    return {
        init: init
    };
});
