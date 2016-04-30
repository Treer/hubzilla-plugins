<h2>{{$title}}</h2>

<form action="map/import" method="post" enctype="multipart/form-data" id="import-map-form">

	<div id="import-desc" class="descriptive-paragraph"><p>{{$desc}}</p></div>

	<label for="import-filename" id="label-import-filename" class="import-label" >{{$label_filename}}</label>
	<input type="file" name="filename" id="import-filename" class="import-input" value="" />
	<div id="import-filename-end" class="import-field-end"></div>
        <br>
	<input type="submit" name="submit" id="import-submit-button" value="{{$submit}}" />
	<div id="import-submit-end" class="import-field-end"></div>
        <br>
	<div id="import-common-desc" class="descriptive-paragraph"><a href="/map">{{$returntomap}}</a></div>

</form>

