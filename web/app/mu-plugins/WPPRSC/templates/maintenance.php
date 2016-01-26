<!DOCTYPE html>
<html <?php echo $data['language_attributes']; ?>>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<title><?php echo $data['title']; ?></title>
		<link rel="stylesheet" href="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/css/bootstrap.min.css">
		<script src="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<main id="main" class="col-md-12">
					<h1><?php echo $data['title']; ?></h1>
					<p><?php echo $data['description']; ?></p>
				</main>
			</div>
		</div>
	</body>
</html>
