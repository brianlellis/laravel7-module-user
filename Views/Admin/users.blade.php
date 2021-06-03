<div class="form-panel">
  <h1 class="mb"><i class="fa fa-angle-right"></i> View Users</h1>
  
  <table data-toggle="table" data-url="https://api.github.com/users/wenzhixin/repos?type=owner&sort=full_name&direction=asc&per_page=10&page=1" data-sort-name="stargazers_count" data-sort-order="desc">
      <thead>
          <tr>
              <th data-field="name" data-sortable="true"> Name </th>
              <th data-field="stargazers_count" data-sortable="true"> Stars </th>
              <th data-field="forks_count" data-sortable="true"> Forks </th>
              <th data-field="description" data-sortable="true"> Description </th>
          </tr>
      </thead>
  </table>
</div>

@section('page_bottom_scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.0/bootstrap-table.min.js"></script>
@endsection
