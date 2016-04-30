<div class="widget">
    <h3>{{$asidetitle}}</h3>
    <div class="panel-group" id="map-controls" role="tablist" aria-multiselectable="true">
        <div class="panel">
            <div class="section-subtitle-wrapper" role="tab" id="map-controls-tracking"  data-toggle="collapse" data-parent="#map-controls" href="#map-controls-tracking-collapse" aria-expanded="true" aria-controls="map-controls-tracking-collapse">
                <h3 class="bg-success">
                    <p></p>
                    <p class="text-center">My Location
                    </p>
                </h3>
            </div> <!-- section-subtitle-wrapper -->
            <div id="map-controls-tracking-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="map-controls-tracking">
                <div class="section-content-tools-wrapper">

                    <ul class="nav nav-pills nav-stacked">
                        <li><button id="toggle-tracking" class="btn btn-primary btn-sm">Track Location</button></li>
                        <li>
                            <div id="save-location-group" class="form-group field checkbox">
                                <button id="save-location" class="btn btn-primary btn-sm disabled">Save Location</button>                            
                                <div id="autosave-location-container" class="pull-right"><input type="checkbox" name='autosave-location' id='autosave-location' value="0"   /><label class="switchlabel" for='autosave-location'> <span class="onoffswitch-inner" data-on='Auto on' data-off='Auto off'></span><span class="onoffswitch-switch"></span></label></div>
                            </div>
                        </li>
                        <li>
                            <div id="show-history-group" class="form-group field checkbox">
                                <button id="history-dialog" class="btn btn-primary btn-sm" type="button">Display location history</button>
                                <button id="delete-all-dynamic-shares" class="btn btn-danger btn-sm" type="button">Revoke all shares</button>
                            </div>
                        </li>
                    </ul>

                </div>
            </div> <!-- map-controls-tracking-collapse -->

        </div> <!-- panel -->

        <div class="panel">
            <div class="section-subtitle-wrapper bg-success" role="tab" id="map-controls-sharing" data-toggle="collapse" data-parent="#map-controls" href="#map-controls-sharing-collapse" aria-expanded="true" aria-controls="map-controls-sharing-collapse">
                <h3  class="bg-success">
                    <p></p>
                    <p class="text-center">Share My Location
                    </p>
                </h3>
            </div> <!-- section-subtitle-wrapper -->
            <div id="map-controls-sharing-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="map-controls-sharing">
                <div class="section-content-tools-wrapper">

                    <ul class="nav nav-pills nav-stacked">
                        <li><button id="share-location" class="btn btn-success btn-sm pull-right">Share Location</button>
                            <div id="acl-container">
                            <div id="profile-jot-submit-right" class="btn-group pull-left">
                                <button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="Permission settings" onclick="return false;">
                                    <i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons">{{$bang}}</i>
                                </button>
                            </div>
                            </div>                            
                        </li>
                    </ul>
                    <ul class="nav nav-pills nav-stacked">
                        <li>
                            <div id="post-visible-container" class="form-group field checkbox"> 
                            <span>Send notification post?</span>                            
                            <div class="pull-right">
                                <input type="checkbox" name="post-visible" id="post-visible" value="0" />
                                <label class="switchlabel" for="post-visible"> 
                                    <span class="onoffswitch-inner" data-on='Post' data-off='None'></span>
                                    <span class="onoffswitch-switch"></span>
                                </label>
                            </div>
                            </div>
                        </li>
                    </ul>

                    <ul class="nav nav-pills nav-stacked">
                        <li>
                            <div id="dynamic-location-share-link-container">
                            <span>Share link:</span>                            
                            <input type="text" id="dynamic-location-share-link" onClick="this.select();" READONLY></input>
                            </div>
                        </li>
                    </ul>

                </div>
            </div> <!-- map-controls-sharing-collapse -->

        </div> <!-- panel -->


        <div class="panel">
            <div class="section-subtitle-wrapper bg-success" role="tab" id="map-controls-markers" data-toggle="collapse" data-parent="#map-controls" href="#map-controls-markers-collapse" aria-expanded="true" aria-controls="map-controls-markers-collapse">
                <h3 class="bg-success">
                    <p></p>
                    <p class="text-center">Markers
                    </p>
                </h3>
            </div> <!-- section-subtitle-wrapper -->
            <div id="map-controls-markers-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="map-controls-markers">
                <div class="section-content-tools-wrapper">

                    <ul class="nav nav-pills nav-stacked" id="my-markers-list">
                    </ul>

                </div>
            </div> <!-- map-controls-markers-collapse -->

        </div> <!-- panel -->


        <div class="panel">
            <div class="section-subtitle-wrapper bg-success" role="tab" id="map-controls-shared" data-toggle="collapse" data-parent="#map-controls" href="#map-controls-shared-collapse" aria-expanded="true" aria-controls="map-controls-shared-collapse">
                <h3 class="bg-success">
                    <p></p>
                    <p class="text-center">Shared with Me
                    </p>
                </h3>
            </div> <!-- section-subtitle-wrapper -->
            <div id="map-controls-shared-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="map-controls-shared">
                   
                <div class="section-content-tools-wrapper">
                    <ul class="nav nav-pills nav-stacked" id="shared-markers-list">
                        
                    </ul>
                </div>
            </div> <!-- map-controls-shared-collapse -->

        </div> <!-- panel -->

        <div class="panel">
            <div class="section-subtitle-wrapper bg-success" role="tab" id="map-controls-events" data-toggle="collapse" data-parent="#map-controls" href="#map-controls-events-collapse" aria-expanded="true" aria-controls="map-controls-events-collapse">
                <h3 class="bg-success">
                    <p></p>
                    <p class="text-center">Events
                    </p>
                </h3>
            </div> <!-- section-subtitle-wrapper -->
            <div id="map-controls-events-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="map-controls-events">
                   
                <div class="section-content-tools-wrapper">
                    <ul class="nav nav-pills nav-stacked" id="events-markers-list">
                        
                    </ul>
                </div>
            </div> <!-- map-controls-events-collapse -->

        </div> <!-- panel -->

    </div> <!-- panel-group -->

    <div>{{$acl}}</div>
</div> <!-- widget -->

<div class="hide" id="shared-marker-dropdown-menu">
    <div class="dropdown pull-right">
        <button id="shared-marker-dropdown-button" class="btn btn-default btn-xs" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-caret-down"></i>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dLabel">
            <li id="shared-marker-dropdown-menu-center"><a  href="" title="Center" onclick="return false;">Center</a></li>
            <li class="divider"></li>
            <li id="shared-marker-dropdown-menu-save"><a  href="" title="Save" onclick="return false;">Save</a></li>
            <li id="shared-marker-dropdown-menu-remove"><a  href="" title="Remove" onclick="return false;">Remove</a></li>
        </ul>
    </div>
</div>
<p class="descriptive-text" style="margin-left: 15px;">{{$version}}</p>