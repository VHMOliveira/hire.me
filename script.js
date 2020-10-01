// remap jQuery to $
(function($){

	/* trigger when page is ready */
	$(document).ready(function (){
	
		$('#submitGetShort').on('click', () => {
            
            var urlLong = $('#myURL').val();
            var custom_alias = $('#custom_alias').val();
            var time;
            $.ajax({
                type:'POST',
                url:'app.php',
                start_time: new Date().getTime(),
                data: {recover:false, urlLong:urlLong, urlShort:custom_alias},
            }).done(function( data ) {
                time = (new Date().getTime() - this.start_time)+' ms';
                if(!data.search('<br />')){
                    var split = data.split("<br />")
                    var dataObject =JSON.parse(split[2])
                    $('#alaias').html( "alias: " + dataObject.url.value)
                    $('#resultado').html( "Resultado: ")
                    if(dataObject.url.errorCode == '001'){
                        $('#errorcode').html("err_code: " + dataObject.url.errorCode)
                        $('#redirect').html('')
                        $('#error').html("description: " + dataObject.url.value)
                    }else{
                        $('#redirect').html(dataObject.prefix + dataObject.url.value);
                        $('#time').html("time_taken: " +time);
                    }

                }else{
                    var dataObject = JSON.parse(data)
                    if(dataObject.url.errorCode == '001'){
                        $('#errorcode').html("err_code: " + dataObject.url.errorCode)
                        $('#redirect').html('')
                        $('#error').html("description: " + dataObject.url.value)
                    }else{
                        $('#redirect').html(dataObject.prefix + dataObject.url.value);
                        $('#time').html("time_taken: " +time);
                    }
                }
            });
            
        })

        $('#submitRecoverShort').on('click', () => {
            
            var urlLong = $('#myshortURL').val();
            var custom_alias = '';
            var time;
            $.ajax({
                type:'POST',
                url:'app.php',
                start_time: new Date().getTime(),
                data: {recover: true,urlLong:urlLong, urlShort:custom_alias},
            }).done(function( data ) {
                var dataObject = JSON.parse(data)
                $('#recovery').html(dataObject.url)
                $("#recovery").attr("href", dataObject.url);
            });
            
        })	
	});

})(window.jQuery);