<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teacher Approvals | Bubog NHS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: #0f172a; padding: 2rem 1.5rem 4rem; }
        .page-header { max-width: 1100px; margin: 0 auto 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .page-header h1 { font-size: 1.5rem; font-weight: 800; }
        .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 0.25rem; }
        .back-link { color: #1e88e5; font-weight: 600; text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { text-decoration: underline; }
        .container { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
        .alert { padding: 0.85rem 1.1rem; border-radius: 0.6rem; font-size: 0.9rem; font-weight: 600; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .card { background: #fff; border-radius: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { padding: 1.1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: 1.05rem; font-weight: 700; }
        .badge { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.85rem 1.4rem; font-size: 0.88rem; border-bottom: 1px solid #f1f5f9; }
        th { color: #64748b; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:last-child td { border-bottom: none; }
        .empty-row td { text-align: center; color: #94a3b8; padding: 1.5rem; }
        .actions { display: flex; gap: 0.5rem; }
        .btn { padding: 0.4rem 0.85rem; border: none; border-radius: 0.4rem; cursor: pointer; font-size: 0.82rem; font-weight: 600; }
        .btn-approve { background: #10b981; color: #fff; }
        .btn-reject { background: #ef4444; color: #fff; }
        .btn-reset { background: #e2e8f0; color: #334155; }
        .pagination { padding: 1rem 1.4rem; }
    </style>
</head>
<body>
    <div class="page-header">
        <div>
            <h1>Teacher Approvals</h1>
            <p>Review and manage teacher accounts on the platform</p>
        </div>
        <a class="back-link" href="{{ route('admin.dashboard') }}">&larr; Back to dashboard</a>
    </div>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h2>Pending <span class="badge badge-pending">{{ $pendingTeachers->total() }}</span></h2>
            </div>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Requested</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($pendingTeachers as $teacher)
                        <tr>
                            <td>{{ $teacher->name }}</td>
                            <td>{{ $teacher->email }}</td>
                            <td>{{ $teacher->created_at->diffForHumans() }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('admin.teacher.approve', $teacher->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.teacher.reject', $teacher->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="4">No pending teachers.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">{{ $pendingTeachers->links() }}</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Approved <span class="badge badge-approved">{{ $approvedTeachers->total() }}</span></h2>
            </div>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($approvedTeachers as $teacher)
                        <tr>
                            <td>{{ $teacher->name }}</td>
                            <td>{{ $teacher->email }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('admin.teacher.reset', $teacher->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-reset">Reset to pending</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="3">No approved teachers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">{{ $approvedTeachers->links() }}</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Rejected <span class="badge badge-rejected">{{ $rejectedTeachers->total() }}</span></h2>
            </div>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($rejectedTeachers as $teacher)
                        <tr>
                            <td>{{ $teacher->name }}</td>
                            <td>{{ $teacher->email }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('admin.teacher.reset', $teacher->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-reset">Reset to pending</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="3">No rejected teachers.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">{{ $rejectedTeachers->links() }}</div>
        </div>
    </div>
</body>
</html>
