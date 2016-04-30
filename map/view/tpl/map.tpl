
<div id="map" class="map">
    <div id="info"></div>
    <div id="recenter-control" class="ol-control"><img src='addon/map/center-control.png' width="20px"></div>
    <div id="marker-popup" class="ol-popup">
        <a href="#" id="marker-popup-closer" class="ol-popup-closer"></a>
        <div id="marker-popup-content">
            <div id="marker-popup-context-menu"></div>
            <div id="add-marker" class="btn btn-default"><a href="" onclick="return false;">Create marker</a></div>
            <div id="add-event" class="btn btn-default"><a href="" onclick="return false;">Create event</a></div>
        </div>
    </div>
</div>

<div class="hide" id="data-cache">{{$data_cache}}</div>
<div class="hide" id="map-login-form">{{$loginbox}}</div>
<div class="modal" id="generic-dialog" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="generic-dialog-title">Title</h4>
            </div>
            
            <div class="modal-body" id="generic-dialog-body">
                    
            </div>
            <div class="modal-footer">
                    <button class="btn btn-sm btn-danger pull-left" title="Cancel" id="generic-dialog-cancel" onclick="return false;">Cancel</button>
                    <div class="btn-group pull-right">                    
                        <span id="generic-dialog-acl-container"></span>                    
                        <button type="submit" class="btn btn-primary" id="generic-dialog-submit" name="generic-dialog-submit" value="1">OK</button>
                    </div>
                    
            </div>
        </div>
    </div>
</div>