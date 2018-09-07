<?php

  register_html_resource('css', 'gridstack.min.css');
  register_html_resource('js', 'lodash.min.js');
  register_html_resource('js', 'jquery-ui.min.js');
  register_html_resource('js', 'gridstack.all.js');

  // We have to load these here.
  register_html_resource('css', 'leaflet.css');
  register_html_resource('js', 'leaflet.js');

  include_dir($config['html_dir'] . '/includes/widgets/');


  $dashboard = dbFetchRow("SELECT * FROM `dashboards` WHERE `dash_id` = ?", array($vars['dash']));

  if(is_array($dashboard))
  {


?>

<div style="margin-bottom: 10px;">
<!--
    <a class="btn btn-default" id="add-new-widget">Add Widget</a>
    <a class="btn btn-default" id="save-grid">Save Grid</a>
    <a class="btn btn-default" id="load-grid">Load Grid</a>
    <a class="btn btn-default" id="clear-grid">Clear Grid</a>
    <a class="btn btn-default" id="refresh-widgets">Refresh Widgets</a>
-->
    <form id="add_widget">
        <select id="widget_type" name="widget_type">
          <?php
            $types = array('map' => 'Map',
                           'alert_table' => 'Alert Table',
                           'alertlog' => 'Alert Log',
                           'graph' => 'Graph', // Doesn't work adding here
                           'port_percent' => 'Traffic Composition',
                           //'status_summary' => "Status Summary", // broke
                           //'status_donuts'  => "Status Donuts",  // broke
                           'syslog' => 'Syslog',
                           'eventlog' => 'Eventlog');

            foreach ($types as $type => $descr)
            {
              echo '<option value="'.$type.'">'.$descr.'</option>';
            }
          ?>
        </select>
        <input type="hidden" id="dash_id" name="dash_id" value="<?php echo $dashboard['dash_id']; ?>">
        <input type="submit" value="Add">
    </form>
</div>

<?php

echo '<div class="row">';
echo '<div class="grid-stack">';
echo '</div>';
echo '</div>';

?>

<!--- <textarea id="saved-data" cols="100" rows="20" readonly="readonly"></textarea> -->

<script type="text/javascript">

    var dash_id = <?php echo $dashboard['dash_id']; ?>


    function isNumber(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    $(function () {
        var options = {
            cellHeight: 80,
            horizontalMargin: 20,
            verticalMargin: 10,
            resizable: {
                autoHide: true,
                handles: 'se, sw'
            },
            draggable: {
                handle: '.drag-handle',
            }
        };
        $('.grid-stack').gridstack(options);

            var initial_grid = [

                <?php

                    $data = array();
                    $widgets = dbFetchRows("SELECT * FROM `dash_widgets` WHERE `dash_id` = ?", array($dashboard['dash_id']));
                    foreach ($widgets AS $w)
                    {
                      $data[] = '{' .
                                 (is_numeric($w['x']) ? '"x": '.$w['x'].',' : '') .
                                 (is_numeric($w['y']) ? '"y": '.$w['y'].',' : '') .
                                 '"width": '.$w['width'].', "height": '.$w['height'].', "id": "'.$w['widget_id'].'"}';
                    }

                    echo implode(",", $data);

                ?>

            ];

            this.grid = $('.grid-stack').data('gridstack');

            var grid = $('.grid-stack').data('gridstack');

            ///////////////
            // LOAD GRID //
            ///////////////

            this.loadGrid = function () {
                this.grid.removeAll();
                var items = GridStackUI.Utils.sort(initial_grid);
                var self = this;
                _.each(items, function (node) {
                    node.autoposition = null;
                    self.drawWidget(node);
                }, this);
                return false;
            }.bind(this);

            ///////////////
            // SAVE GRID //
            ///////////////

            this.saveGrid = function () {
                this.initial_grid = _.map($('.grid-stack > .grid-stack-item:visible'), function (el) {
                    el = $(el);
                    var node = el.data('_gridstack_node');
                    return {
                        x: node.x,
                        y: node.y,
                        width: node.width,
                        height: node.height,
                        id: el.attr('data-gs-id'),
                    };
                }, this);
                $('#saved-data').val(JSON.stringify(this.initial_grid, null, '    '));

                var self = this;

                // We need to get a widget id via AJAX
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: { action : 'save_grid', grid : self.initial_grid},
                    cache: false,
                    success: function(response) {
                        console.log(response);
                        console.log(self.initial_grid);
                    }
                });

                return false;
            }.bind(this);

            this.clearGrid = function () {
                this.grid.removeAll();
                return false;
            }.bind(this);

            /////////////////////
            // DRAW THE WIDGET //
            /////////////////////

            this.drawWidget = function(node) {
                this.grid.addWidget($('<div><div id="widget-' + node.id +'" class="grid-stack-item-content" />' +
                    '<div class="hover-show" style="z-index: 900; position: absolute; top:0px; right: 10px; padding: 2px 10px; padding-right: 0px; border-bottom-left-radius: 4px; border: 1px solid #e5e5e5; border-right: none; border-top: none; background: white;">' +
                    '  <i style="cursor: pointer; margin: 7px;" class="sprite-refresh" onclick="refreshWidget(' + node.id + ')"></i>' +
                    '  <i style="cursor: pointer; margin: 7px;" class="sprite-tools" onclick="configWidget(' + node.id + ')"></i></i>' +
                    '  <i style="cursor: no-drop; margin: 7px;" class="sprite-cancel" onclick="deleteWidget(' + node.id + ')"></i>' +
                    '  <i style="cursor: move; margin: 7px; margin-right: 20px" class="sprite-move drag-handle"></i>' +
                    '</div>' +
                    '<div/>'),
                    node.x, node.y, node.width, node.height, node.autoposition, null, null, null, null,node.id);
            };

            ////////////////
            // ADD WIDGET //
            ////////////////

            addNewWidget = function (type, dash_id) {

                // We need to get a widget id via AJAX
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param( [ { name: 'action', value: 'add_widget'}, { name: "widget_type", value: type }, { name: "dash_id", value: dash_id } ] ),
                    cache: false,
                    success: function(response) {

                        if(isNumber(response.id)){

                            // Create the initial widget array use autoposition and id from ajax
                            var node = {
                                width: 4,
                                height: 3,
                                autoposition: true,
                                id: response.id
                            };

                            // Draw the widget
                            self.drawWidget(node);

                            // Save grid
                            self.saveGrid();

                            // Redraw widgets
                            self.refreshAllWidgets(response.id);

                        }
                        console.log(response);
                    }
                });

                return false;
            }.bind(this);

            /////////////////////////
            // Refresh All Widgets //
            /////////////////////////

            this.refreshAllWidgets = function () {
                $('.grid-stack-item').each(function () {
                    refreshWidget($(this).attr('data-gs-id'));
                });
            };

        refreshAllUpdatableWidgets = function () {
            $('.grid-stack-item').each(function () {
                if($(this).children('div').children('div').hasClass('gridstack-update')) {
                    refreshWidget($(this).attr('data-gs-id'));
                }
            });
        };


            ///////////////////////////
            // Refresh single widget //
            ///////////////////////////

            refreshWidget = function (id) {
                // This is the content div to be updated
                var div = $('#widget-' + id);

                // Generate array of parameters to send to the server.
                var params = {
                    //width: widget.attr('data-gs-width'),
                    //height: widget.attr('data-gs-height'),
                    width: div.innerWidth(),
                    height: div.innerHeight(),
                    id: id
                };

                // Run AJAX query and update div HTML with response.
                $.ajax({
                    type: "POST",
                    url: "ajax/widget.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function(response) {
                        div.html(response);
                    }
                });
            };

            deleteWidget = function (id)
            {
                var el = $(".grid-stack-item[data-gs-id='" + id + "']");

                var params = {
                    action: 'del_widget',
                    widget_id: id
                };
                // Run AJAX query and update div HTML with response.
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function(response) {
                        grid.removeWidget(el);
                        console.log(response);
                    }
                });
            };

            configWidget = function (id)
            {

                var params = {
                    action: 'edit_widget',
                    widget_id: id
                };

                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function(response) {
                        $('#config-modal-body').html(response);
                        $('#config-modal').modal({show:true});
                    }
                });

            };

            /////////////
            // Actions //
            /////////////

            $('#add-new-widget').click(this.addNewWidget);
            $('#save-grid').click(this.saveGrid);
            $('#load-grid').click(this.loadGrid);
            $('#clear-grid').click(this.clearGrid);
            $('#refresh-widgets').click(this.refreshAllWidgets);

            $( "#add_widget" ).submit(function( event ) {
              addNewWidget( $('#widget_type').val(), $('#dash_id').val() );
              event.preventDefault();
            });


            this.loadGrid();
            this.refreshAllWidgets();

            var self = this;

            $('.grid-stack').on('change', function(event, items) {

                console.log(event);

                items.forEach(function(item) {
                    refreshWidget(item.el.attr('data-gs-id'));
                });
                self.saveGrid();
            });


            setInterval(function() {
                refreshAllUpdatableWidgets();
            }, 3000);



    });

</script>

<?php
// print_vars($widgets);
?>

<style>

    .grid-stack>.grid-stack-item>.grid-stack-item-content
    {
        border-radius: 3px;
        background: #ffffff;
        box-shadow: 0 0 2px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.24);
        overflow-x: visible;
        overflow-y: visible;
    }}

    .grid-stack>.grid-stack-item>.grid-stack-item-content:hover {
        //yoverflow-x: auto;
        //yoverflow-y: auto;

    }

    .grid-stack>.grid-stack-item:hover  .hover-hide
    { display: none;}

    .grid-stack>.grid-stack-item  .hover-show
    { display: none;}

    .grid-stack>.grid-stack-item:hover  .hover-show
    { display: block;}

    .grid-stack>.grid-stack-item h4 {
        margin: 3px 6px;
    }

</style>

<!-- Modal -->
<div class="modal fade" id="config-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Configure Module</h4>

            </div>
            <div id="config-modal-body" class="modal-body"><div class="te"></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<?php

  } else {
      print_error('Dashboard does not exist!');
  }