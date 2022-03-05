	
//$jq(document).ready(function(){
	//$jq('#he-thong').dataTable({"language":{"url": "js/dataTables/language/vi.txt"}});
//});
$jq(document).ready(function() {
    $jq('#hoi-sach').DataTable( {
    "language":{"url": "js/dataTables/language/vi.txt"},
	"lengthMenu": [[30, 50, -1], [30, 50, "All"]],
        responsive: {
            details: {
                type: 'column'
            }
        },
        columnDefs: [ {
            className: 'control',
            orderable: false,
            targets:   0
        } ],
        order: [ 1, 'asc' ]
    } );
} );
