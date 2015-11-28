$(document).ready(function(){
    
    // the difference between two time values
    function differenceInMinutes(start,end){
        var startTime = start.split(':');
        var endTime = end.split(':');
        
        var startTimeInMin = ( parseInt(startTime[0]) * 60 ) + parseInt(startTime[1]);
        var endTimeInMin = ( parseInt(endTime[0]) * 60 ) + parseInt(endTime[1]);

        if(startTimeInMin<=endTimeInMin) {
            var totalDifferenceInMinutes = endTimeInMin - startTimeInMin;
        }else{
            var minutesInADay = 24 * 60 ;
            var totalDifferenceInMinutes = endTimeInMin + (minutesInADay - startTimeInMin);
        }

        return totalDifferenceInMinutes;
    }
    // converts minutes into hours and minutes associative array
    function convertMinutesIntoHours(minutes){
        minutes = parseInt(minutes);
        var hours = Math.floor(minutes / 60);
        var minutesLeft = minutes - (hours * 60);
        return { 'hours':hours , 'minutes':minutesLeft};
    }
    // update the difference field
    function updateDifference(timeRow){
        var startTime = $(timeRow).find('td input.start').val();
        var endTime = $(timeRow).find('td input.end').val();

        var durationInMinutes = differenceInMinutes(startTime,endTime);
        var duration = convertMinutesIntoHours(durationInMinutes);
        $(timeRow).find('td input.duration')
                    .val(duration['hours'] +'h '+ duration['minutes']+'m');
    }
    $('.forms').on('change', 'form table tr td .clockpicker', function(){
        var timeRow = $(this).closest('tr');
        updateDifference(timeRow);
    });

    // adds a 0 if value less than ten
    function timeAddZero(i) {
        if (i < 10) {
            i = "0" + i;
        }
        return i;
    }
    // stop start the timer
    function timertoggle(button){
        var form = $(button).closest('form');
        if($(button).hasClass('play')){
            var time = new Date($.now());
            var startTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
            $(form).find('table tr:first td input.start')
                .val(startTime);
            $(button).html('stop')
                .removeClass('play')
                .addClass('stop');
        }else if($(button).hasClass('stop')){
            time = new Date($.now());
            var endTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
    
            $(form).find('table tr:first td input.end')
                .val(endTime);
            $(button).html('play')
                .removeClass('stop')
                .addClass('play');
        
            var timeRow = $(form).find('table tr:first');
            updateDifference(timeRow);
        }
    }
    $('.forms').on('click', 'form .projectheader .timertoggle', function(){
        timertoggle(this);
    });

    $('.forms').on('focus','.clockpicker', function(){
        $(this).clockpicker({
            placement: 'bottom', // clock popover placement
            align: 'left',       // popover arrow align
            donetext: 'Done',     // done button text
            autoclose: true,    // auto close when minute is selected
            vibrate: true        // vibrate the device when dragging clock hand
        });
    });
    
    $('.forms').on('focus','form table tr td input.datepicker', function(){
        $(this).datepicker({
          "dateFormat": 'd M, y' 
        });
    });
    
    function updateProject(button){
        var form = $(button).closest('form');
        var project = $(form).find('.projectheader .projectselector').val();
        var ticketNo = $(form).find('.projectheader .ticketnumber').val();
        var ticket = project + '-' + ticketNo;
        $.ajax({url: "src/ticketAjax.php?ticket="+ticket, dataType: "json",
            success: function(result){
                $(form).find('.projectheader .ticketsummary').html(result['summary']);
                var html = '<option value=""></option>';
                for (i = 0; i < result['workTypes'].length; i++){
                    html += '<option value="'+result['workTypes'][i]+'">'+result['workTypes'][i]+'</option>';
                }
                $(form).find('table tr td select.type').html(html);
            },
            error: function(result){
            }
        });
    }
    $('.forms').on('change', 'form .projectheader .projectselector', function(){
        updateProject(this);
    });
    $('.forms').on('change', '.ticketnumber', function(){
        updateProject(this);
    });

    function addTimeRow(form){
        var html = $('form.template').find('table tr:first').html();
        $(form).find('table tbody').prepend('<tr>'+html+'</tr>');
        html = $(form).find('table tr:last td select.type').html();
        $(form).find('table tr:first td select.type').html(html);
    }
    $('.forms').on('click', 'form .addTimeRow', function(){
        var form = $(this).closest('form');
        addTimeRow(form);
    });
    
    function removeTimeRow(row){
        var tbody = $(row).parent();
        $(row).remove();
        var rowCount = $(tbody).children('tr').length;
        var form = $(tbody).closest('form');
        if(rowCount === 0){
            addTimeRow(form);
        }
    }
    $('.forms').on('click', '.deleteTimeRow', function() {
        var row = $(this).closest('tr');
        removeTimeRow(row);
    });

    function addTicketForm(){
        var html = $('form.template').html();
        $('div.forms').prepend('<form action="src/timeTrackerSubmit.php" method="post" enctype="multipart/form-data">'+html+'</form>');
    }
    $('body').on('click','.addTicketForm',function(){
       addTicketForm();
    });
    
});