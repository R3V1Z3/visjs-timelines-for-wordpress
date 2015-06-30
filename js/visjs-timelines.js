jQuery( document ).ready(function() {
	// DOM element where the Timeline will be attached
	var container = document.getElementById('visualization');

	var parsed = jQuery.parseJSON(visjs_timelines_items);

	// Create a DataSet (allows two way data-binding)
	var vis_items = new vis.DataSet(parsed);

	// Configuration for the Timeline
	var options = {
	  editable: false,
	  clickToUse: false
	};

	// Create a Timeline
	var timeline = new vis.Timeline(container, vis_items, options);

    timeline.on('select', timeline_response );

	function timeline_response(properties){
    	selected = JSON.stringify(properties);
    	json = JSON.parse(selected);
    	post_id = Number(json.items);
    	if (post_id < 1) {return;}
    	// communicate with WP
		jQuery.ajax({
			method: "POST",
			url: visjs_timelines_ajax.ajax_url,
			data: {
				action:'timeline_post_content',
				id: post_id,
				security: visjs_timelines_ajax.ajax_nonce
			},
			beforeSend: function() {
				//jQuery('#timeline_content').html('Loading...');
				jQuery('.timeline-content').empty();
			},
			success: function(response) {
				jQuery(".timeline-content").append("<div class='flyin animateA'></div>");
				jQuery(".flyin").html(response);
				//jQuery('#timeline_content').html(response);
				jQuery(".flyin").outerWidth() && jQuery(".flyin").removeClass("animateA");
				return false;
			}
		});
	}
	timeline.setSelection(65, {focus: focus.checked});
});