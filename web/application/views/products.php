<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>말랑이 목록</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container py-5">
		<h1 class="mb-4">🧸 말랑이 목록</h1>
		<table class="table table-hover bg-white shadow-sm align-middle">
			<thead>
				<tr><th style="width:60px">#</th><th>이름</th><th>브랜드</th></tr>
			</thead>
			<tbody>
				<?php foreach ($products as $p): ?>
					<tr>
						<td><?php echo $p->id; ?></td>
						<td><?php echo $p->name; ?></td>
						<td><?php echo $p->brand; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</body>
</html>
