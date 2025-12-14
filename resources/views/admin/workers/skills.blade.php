@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Skills Management
            <small>Manage Platform Skills</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/workers') }}">Workers</a></li>
            <li class="active">Skills</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <!-- Add New Skill -->
            <div class="col-md-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-plus"></i> Add New Skill</h3>
                    </div>
                    <form method="POST" action="{{ url('panel/admin/workers/skills/create') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label>Skill Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g., Forklift Operation">
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control">
                                    <option value="">Select Category</option>
                                    <option value="general">General</option>
                                    <option value="technical">Technical</option>
                                    <option value="equipment">Equipment Operation</option>
                                    <option value="safety">Safety & Compliance</option>
                                    <option value="customer_service">Customer Service</option>
                                    <option value="physical">Physical</option>
                                    <option value="administrative">Administrative</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this skill..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="requires_certification" value="1">
                                    Requires Certification
                                </label>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-plus"></i> Add Skill
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Skills List -->
            <div class="col-md-8">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> All Skills ({{ $skills->total() }})</h3>
                        <div class="box-tools">
                            <form method="GET" action="{{ url('panel/admin/workers/skills') }}" class="form-inline">
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" name="q" class="form-control" placeholder="Search skills..." value="{{ request('q') }}">
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Skill Name</th>
                                    <th>Category</th>
                                    <th>Workers</th>
                                    <th>Requires Cert</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($skills as $skill)
                                <tr>
                                    <td>{{ $skill->id }}</td>
                                    <td>
                                        <strong>{{ $skill->name }}</strong>
                                        @if($skill->description)
                                            <br>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($skill->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($skill->category)
                                            <span class="label label-info">{{ ucfirst(str_replace('_', ' ', $skill->category)) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-blue">{{ $skill->workers_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @if($skill->requires_certification)
                                            <span class="label label-warning"><i class="fa fa-certificate"></i> Yes</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($skill->is_active)
                                            <span class="label label-success">Active</span>
                                        @else
                                            <span class="label label-default">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-xs btn-info" onclick="editSkill({{ $skill->id }}, '{{ addslashes($skill->name) }}', '{{ $skill->category }}', '{{ addslashes($skill->description ?? '') }}', {{ $skill->requires_certification ? 'true' : 'false' }})">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            @if($skill->is_active)
                                                <button type="button" class="btn btn-xs btn-warning" onclick="toggleSkillStatus({{ $skill->id }}, false)">
                                                    <i class="fa fa-eye-slash"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-xs btn-success" onclick="toggleSkillStatus({{ $skill->id }}, true)">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-xs btn-danger" onclick="deleteSkill({{ $skill->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <p style="padding: 20px;">No skills found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($skills->total() > 0)
                    <div class="box-footer clearfix">
                        {{ $skills->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>

                <!-- Skills by Category -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-pie-chart"></i> Skills by Category</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            @foreach($skillsByCategory as $category => $count)
                            <div class="col-md-3 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $count }}</h5>
                                    <span class="description-text">{{ ucfirst(str_replace('_', ' ', $category ?: 'Uncategorized')) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="{{ url('panel/admin/workers') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Workers
                </a>
            </div>
        </div>
    </section>
</div>

<!-- Edit Skill Modal -->
<div class="modal fade" id="editSkillModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editSkillForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit Skill</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Skill Name *</label>
                        <input type="text" name="name" id="editSkillName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="editSkillCategory" class="form-control">
                            <option value="">Select Category</option>
                            <option value="general">General</option>
                            <option value="technical">Technical</option>
                            <option value="equipment">Equipment Operation</option>
                            <option value="safety">Safety & Compliance</option>
                            <option value="customer_service">Customer Service</option>
                            <option value="physical">Physical</option>
                            <option value="administrative">Administrative</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="editSkillDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="requires_certification" id="editSkillRequiresCert" value="1">
                            Requires Certification
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Skill</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function editSkill(id, name, category, description, requiresCert) {
    document.getElementById('editSkillForm').action = '/panel/admin/workers/skills/' + id + '/update';
    document.getElementById('editSkillName').value = name;
    document.getElementById('editSkillCategory').value = category;
    document.getElementById('editSkillDescription').value = description;
    document.getElementById('editSkillRequiresCert').checked = requiresCert;
    $('#editSkillModal').modal('show');
}

function toggleSkillStatus(skillId, activate) {
    var action = activate ? 'activate' : 'deactivate';
    if (confirm('Are you sure you want to ' + action + ' this skill?')) {
        $.post('/panel/admin/workers/skills/' + skillId + '/toggle-status', {
            _token: '{{ csrf_token() }}',
            is_active: activate
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    }
}

function deleteSkill(skillId) {
    if (confirm('Are you sure you want to delete this skill? This action cannot be undone.')) {
        $.ajax({
            url: '/panel/admin/workers/skills/' + skillId + '/delete',
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
}

$(document).ready(function() {
    $('#editSkillForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(response) {
                $('#editSkillModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    });
});
</script>
@endsection
