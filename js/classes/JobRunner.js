
// init namespace
if ( typeof WpLister != 'object') var WpLister = {};


// revealing module pattern
WpLister.JobRunner = function () {
    
    // this will be a private property
    var jobsQueue = {};
    var jobsQueueActive = false;
    var jobKey = 0;
    var currentTask = 0;
    var currentSubTask = 0;
    var subtaskQueue = {};
    var retryCount = 0;
    var self = {};
    
    // this will be a public method
    var init = function () {
        self = this; // assign reference to current object to "self"
    
        // jobs window "close" button
        jQuery('#jobs_window .btn_close').click( function(event) {
            tb_remove();                    
        }).hide();

    }

    var runJob = function ( jobname, title ) {
        
        // show jobs window
        this.showWindow( title );

        // load task list
        var params = {
            action: 'wpl_jobs_load_tasks',
            job: jobname,
            nonce: 'TODO'
        };
        var jqxhr = jQuery.getJSON( ajaxurl, params )
        .success( function( response ) { 

            // set global queue
            self.jobKey = response.job_key;
            self.jobsQueue = response.tasklist;
            self.jobsQueueActive = true;
            self.currentTask = 0;

            if ( self.jobsQueue.length > 0 ) {
                // run first task
                self.runTask( self.jobsQueue[ self.currentTask ] );
            } else {
                var logMsg = '<div id="message" class="updated" style="display:block !important;"><p>' + 
                'I could not find any matching items. Sorry.' +
                '</p></div>';
                jQuery('#jobs_log').append( logMsg );
                self.updateProgressBar( 1 );
                self.completeJob();
            }


        })
        .error( function(e,xhr,error) { 
            jQuery('#jobs_log').append( "There was a problem fetching the job list.<br>" );
            jQuery('#jobs_log').append( "The server responded: " + e.responseText + "<br>" );
            jQuery('#jobs_window .btn_close').show();
            // alert( "There was a problem fetching the job list. The server responded:\n\n" + e.responseText ); 
            console.log( "error", xhr, error ); 
            console.log( e.responseText ); 
        });

    }

    var runSubTask = function ( subtask ) {

        var currentLogRow = jQuery('#wpl_logRow_'+self.currentTask);

        // logRow: set title
        currentLogRow.find('.logRowTitle').html( subtask.displayName );
        currentLogRow.find('.logRowTitle').html( 'running subtask...'.subtask.displayName );

        // run task
        // task.displayName = 'ID '+self.jobKey; // reset displayName
        var params = {
            action: 'wpl_jobs_run_subtask',
            job: self.jobKey,
            subtask: subtask,
            nonce: 'TODO'
        };
        // var jqxhr = jQuery.getJSON( ajaxurl, params )
        var jqxhr = jQuery.post( ajaxurl, params, null, 'json' )
        .success( function( response ) { 

            // check task success
            if ( response.success ) {
                var statusIconURL = wplister_url + "img/icon-success.png";
                var errors_label  = response.errors.length == 1 ? 'warning' : 'warnings';
            } else {
                var statusIconURL = wplister_url + "img/icon-error.png";                
                var errors_label  = response.errors.length == 1 ? 'error' : 'errors';
            }

            // update row status
            // currentLogRow.find('.logRowStatus').html( '<img src="'+statusIconURL+'" />' );

            // prepare next subtask
            self.currentSubTask++;
            if ( self.currentSubTask < self.subtaskQueue.length ) {

                // run next task
                self.runSubTask( self.subtaskQueue[ self.currentSubTask ] );

            } else {

                // all subtasks complete
                self.nextTask();

            }

        })
        .error( function(e,xhr,error) { 

            // quit on other errors
            jQuery('#jobs_log').append( "A problem occured while processing this task. The server responded with code " + e.status + ": " + e.responseText + "<br>" );
            jQuery('#jobs_window .btn_close').show();
            // alert( "There was a problem running the task '"+task.displayName+"'.\n\nThe server responded:\n" + e.responseText + '\n\nPlease contact support@wplab.com.' ); 
            console.log( "XHR object", e ); 
            console.log( "error", xhr, error ); 
            console.log( e.responseText ); 

        });

    }

    var runTask = function ( task ) {

        // estimate time left
        var time_left = 'estimating time left...';
        if (self.currentTask == 0) {
            self.time_started = new Date().getTime() / 1000;
        } else {
            var current_time = new Date().getTime() / 1000;
            time_running = current_time - self.time_started;
            time_estimated = time_running / self.currentTask * self.jobsQueue.length;
            time_left = time_estimated - time_running;
            if ( time_left > 60 ) {
                time_left = 'about '+Math.round(time_left/60)+' min. remaining';
            } else {
                time_left = 'about '+Math.round(time_left)+' sec. remaining';
            }
        }

        // update message
        jQuery('#jobs_message').html('processing '+(self.currentTask+1)+' of '+self.jobsQueue.length + ' - ' + time_left);
        this.updateProgressBar( (self.currentTask + 1) / self.jobsQueue.length );

        // create new log row for currentTask
        var new_row = ' <div id="wpl_logRow_'+self.currentTask+'" class="logRow">' +
                        '   <div class="logRowTitle"></div>' +
                        '   <div class="logRowErrors"></div>' +
                        '   <div class="logRowStatus"></div>' +
                        '</div>';
        jQuery('#jobs_log').append( new_row );
        var currentLogRow = jQuery('#wpl_logRow_'+self.currentTask);


        // logRow: set title
        currentLogRow.find('.logRowTitle').html( task.displayName );

        // logRow: set status icon
        var statusIconURL = wplister_url + "img/ajax-loader.gif";
        currentLogRow.find('.logRowStatus').html( '<img src="'+statusIconURL+'" />' );

        // run task
        // task.displayName = 'ID '+self.jobKey; // reset displayName
        var params = {
            action: 'wpl_jobs_run_task',
            job: self.jobKey,
            task: task,
            nonce: 'TODO'
        };
        // var jqxhr = jQuery.getJSON( ajaxurl, params )
        var jqxhr = jQuery.post( ajaxurl, params, null, 'json' )
        .success( function( response ) { 

            if ( response.subtasks && response.success ) {
    
                self.subtaskQueue = response.subtasks;
                self.currentSubTask = 0;
                if ( self.subtaskQueue.length > 0 ) {
                    // run first subtask
                    self.runSubTask( self.subtaskQueue[ self.currentSubTask ] );
                    return;
                }
            }

            // check task success
            if ( response.success ) {
                var statusIconURL = wplister_url + "img/icon-success.png";
                var errors_label  = response.errors.length == 1 ? 'warning' : 'warnings';
            } else {
                var statusIconURL = wplister_url + "img/icon-error.png";                
                var errors_label  = response.errors.length == 1 ? 'error' : 'errors';
            }

            // update row status
            currentLogRow.find('.logRowStatus').html( '<img src="'+statusIconURL+'" />' );

            // handle errors
            if ( response.errors.length > 0 ) {

                // create show details button
                var taskDetailsBtn = '<a href="#" onclick="jQuery(\'#taskDetails_'+self.currentTask+'\').slideToggle(300);return false;" class="" style="">'+response.errors.length + ' '+errors_label+'</a>';
                currentLogRow.find('.logRowErrors').html( taskDetailsBtn );

                // add errors and warnings to hidden div
                var taskDetails = '<div id="taskDetails_'+self.currentTask+'" class="taskDetails" style="display:none;">';
                for (var i = response.errors.length - 1; i >= 0; i--) {
                    var err = response.errors[i]
                    taskDetails += err.HtmlMessage + "<!br>";
                };
                taskDetails += '</div>';
                jQuery('#jobs_log').append( taskDetails );

            }

            // next task
            self.nextTask();

        })
        .error( function(e,xhr,error) { 
            // update row status
            var statusIconURL = wplister_url + "img/icon-error.png";                
            currentLogRow.find('.logRowStatus').html( '<img src="'+statusIconURL+'" />' );

            // default error handling mode: skip
            // if ( typeof wplister_ajax_error_handling === 'undefined' ) wplister_ajax_error_handling = 'skip';

            // dont get fooled by 404 or 500 errors for admin-ajax.php
            if ( ( e.status == 404 ) || ( e.status == 500 ) ) {


                if ( ( wplister_ajax_error_handling == 'retry') && ( self.retryCount < 5 ) ) {

                    // try running the task again
                    self.retryCount++;
                    jQuery('#jobs_log').append( "Warning: server returned "+e.status+". will try again...<!br>" );
                    self.runTask( self.jobsQueue[ self.currentTask ] );

                } else if ( wplister_ajax_error_handling == 'skip') {

                    // prepare next task
                    self.currentTask++;
                    if ( self.currentTask < self.jobsQueue.length ) {
                        // run next task
                        self.runTask( self.jobsQueue[ self.currentTask ] );
                    } else {
                        // all tasks complete
                        jQuery('#jobs_message').html('finishing up...');
                        self.completeJob();
                    }

                } else { // halt

                    // halt task processing
                    jQuery('#jobs_log').append( "A problem occured while processing this task. The server responded with code " + e.status + ": " + e.responseText + "<br>" );
                    jQuery('#jobs_window .btn_close').show();

                }

            // } else if ( e.status == 500 ) {

            //     // just try running the task again
            //     jQuery('#jobs_log').append( "Warning: server returned 500. going to try again...<br>" );
            //     self.runTask( self.jobsQueue[ self.currentTask ] );

            } else {
    
                // quit on other errors
                jQuery('#jobs_log').append( "A problem occured while processing this task. The server responded with code " + e.status + ": " + e.responseText + "<br>" );
                jQuery('#jobs_window .btn_close').show();
                // alert( "There was a problem running the task '"+task.displayName+"'.\n\nThe server responded:\n" + e.responseText + '\n\nPlease contact support@wplab.com.' ); 
                console.log( "XHR object", e ); 
                console.log( "error", xhr, error ); 
                console.log( e.responseText ); 

            }


        });

    }

    var nextTask = function () {

        self.currentTask++;
        self.retryCount=0;
        if ( self.currentTask < self.jobsQueue.length ) {

            // run next task
            self.runTask( self.jobsQueue[ self.currentTask ] );

        } else {

            // all tasks complete
            jQuery('#jobs_message').html('finishing up...');
            self.completeJob();

        }

    }

    var completeJob = function () {

        // inform server of completed job
        var params = {
            action: 'wpl_jobs_complete_job',
            job: self.jobKey,
            nonce: 'TODO'
        };
        var jqxhr = jQuery.getJSON( ajaxurl, params )
        .success( function( response ) { 

            // append to log
            jQuery('#jobs_log').append( response.error );

            // all tasks complete
            self.jobsQueueActive = false;
            jQuery('#jobs_message').html('&nbsp;');
            // jQuery('#jobs_window .btn_close').show();

            if ( self.jobsQueue.length > 0 ) {
                jQuery('#job_bottom_notice').html( 'All ' + self.jobsQueue.length + ' tasks have been completed.' );

                // if there were any tasks completed, refresh the current page when closing the jobs window
                jQuery('#jobs_window .btn_close').click( function(event) {
                    // refresh page
                    // window.location.href = window.location.href;
                    // history.go(0); // alternative

                    // refresh the page - without any action parameter that might be present
                    if ( window.location.href.indexOf("&action") != -1 ) {
                        window.location.href = window.location.href.substr( 0, window.location.href.indexOf("&action") )
                    } else {
                        window.location.href = window.location.href;
                    }
                }).show();

            } else {                
                jQuery('#job_bottom_notice').html( '' );
                jQuery('#jobs_window .btn_close').show();
            }

        })
        .error( function(e,xhr,error) { 
            jQuery('#jobs_log').append( "problem completing job - server responded: " + e.responseText + "<br>" );
            jQuery('#jobs_window .btn_close').show();
            alert( "There was a problem completing this job.\n\nThe server responded:\n" + e.responseText + '\n\nPlease contact support@wplab.com.' ); 
            console.log( "error", xhr, error ); 
            console.log( e.responseText ); 
        });

    }

    
    // show jobs window
    var showWindow = function ( title ) {

        // show jobs window
        var tbHeight = tb_getPageSize()[1] - 160;
        var tbURL = "#TB_inline?height="+tbHeight+"&width=500&modal=true&inlineId=jobs_window_container"; 
        jQuery('#jobs_log').html('').css('height', tbHeight - 130 );
        jQuery('#jobs_title').html( title );
        jQuery('#jobs_message').html('fetching list of tasks...');
        jQuery('#job_bottom_notice').html( "Please don't close this window until all tasks are completed." );

        // init progressbar
        jQuery("#progressbar").progressbar({ value: 0.01 });
        jQuery("#progressbar").children('span.caption').html('0%');

        // hide close button
        jQuery('#jobs_window .btn_close').hide();

        // show window
        tb_show("Jobs", tbURL);             

    }

    var updateProgressBar = function ( value ) {
        // jQuery("#progressbar").progressbar({ value: value });
        jQuery("#progressbar").animate_progressbar( value * 100, 500 );
    }

    return {
        // declare which properties and methods are supposed to be public
        init: init,
        runJob: runJob,
        runTask: runTask,
        nextTask: nextTask,
        completeJob: completeJob,
        updateProgressBar: updateProgressBar,
        showWindow: showWindow
    }
}();


// animate_progressbar() method for progressbar
// http://stackoverflow.com/questions/5047498/how-do-you-animate-the-value-for-a-jquery-ui-progressbar
// (function(a){a.fn.animate_progressbar=function(d,e,f,b){if(d==null){d=0}if(e==null){e=1000}if(f==null){f="swing"}if(b==null){b=function(){}}var c=this.find(".ui-progressbar-value");c.stop(true).animate({width:d+"%"},e,f,function(){if(d>=99.5){c.addClass("ui-corner-right")}else{c.removeClass("ui-corner-right")}b()})}})(jQuery);
(function( jQuery ) {
    jQuery.fn.animate_progressbar = function(value,duration,easing,complete) {
        if (value == null)value = 0;
        if (duration == null)duration = 1000;
        if (easing == null)easing = 'swing';
        if (complete == null)complete = function(){};
        var progress = this.find('.ui-progressbar-value');
        var caption  = this.find('span.caption');
        progress.stop(true).animate({
            width: value + '%'
        },duration,easing,function(){
            if(value>=99.5){
                progress.addClass('ui-corner-right');
            } else {
                progress.removeClass('ui-corner-right');
            }
            caption.html(Math.round(value)+'%');
            complete();
        });
    }
})( jQuery );

