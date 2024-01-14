<?php
if(isset($_GET['query']) && !empty($_GET['query'])){
	$query = explode(',', $_GET['query']);
	$result = array();
	foreach($query as $q){
		$queryString = http_build_query([
			'api_key' => 'demo',
			'q' => $q
		]);
		# make the http GET request to VALUE SERP
		$ch = curl_init(sprintf('%s?%s', 'https://api.valueserp.com/search', $queryString));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		# the following options are required if you're using an outdated OpenSSL version
		# more details: https://www.openssl.org/blog/blog/2021/09/13/LetsEncryptRootCertExpire/
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		$api_result = curl_exec($ch);
		curl_close($ch);
		$api_result = json_decode($api_result);
		$result[] = json_decode(json_encode($api_result), true);
		if($_GET['submit'] === 'Export CSV'){
			$rows[] = array(
				'term' => 'Term',
				'position' => 'Position',
				'title' => 'Title',
				'link' => 'Link',
				'snippet' => 'Snippet'
			);
			foreach($result as $res){
				foreach($res['organic_results'] as $r){
					$rows[] = array(
						'term' => $res['search_parameters']['q'],
						'position' => $r['position'],
						'title' => $r['title'],
						'link' => $r['link'],
						'snippet' => $r['snippet']
					);
				}
			}
			ob_start();
			$fp = fopen('php://output', 'w');
			foreach($rows as $row) fputcsv($fp, $row);
			$data = file_get_contents('php://output'); 
			$name = 'data-'.date('d-M-Y').'.csv';
			header('Content-Description: File Transfer');
			header('Content-Type: application/csv');
			header('Content-Disposition: attachment; filename='.$name);
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			exit();
			force_download($name, $data);
			fclose($fp);
			ob_end_flush();
		}
	}
}
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
		<link rel="stylesheet" type="text/css" href="style.css">
		<title>Demo App - Value SERP</title>
	</head>
	<body>
		<div>
			<div class="box">
				<h1 class="text-center">Search here and Get results!</h1>
				<form name="searchForm" method="GET" autocomplete="off">
					<div class="form-group">
						<label>Enter Search Query (Use ( , ) for multiple search query)</label>
						<input type="text" name="query" class="form-control" value="<?php echo (isset($_GET['query'])?$_GET['query']:'') ?>" placeholder="McDonald, Pizza, Burger">
						<p id="err" style="color: red; font-size: 12px;"></p>
					</div>
					<div class="btn-group" role="group">
						<input type="submit" name="submit" class="btn btn-success" value="Get Result">
						<input type="submit" name="submit" class="btn btn-info" value="Export CSV">
						<a href="/index.php" class="btn btn-danger">Reset</a>
					</div>
				</form>
			</div>
			<div class="box-table">
				<?php if(isset($result)){
					foreach($result as $res){
					?>
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Term</th>
								<th>Position</th>
								<th>Title</th>
								<th>Link</th>
								<th>Snippet</th>
							</tr>
						</thead>
						<tbody>
						<?php
						if(isset($res['organic_results']) || !empty($res['organic_results'])){
							foreach($res['organic_results'] as $r){ ?>
							<tr>
								<td><?php echo $res['search_parameters']['q'] ?></td>
								<td><?php echo $r['position'] ?></td>
								<td><?php echo $r['title'] ?></td>
								<td><?php echo $r['link'] ?></td>
								<td><?php echo $r['snippet'] ?></td>
							</tr>
						<?php }
						 } else{
						 	echo '<tr><td colspan="5" class="text-center">No records found!</td></tr>';
						 } ?>
						</tbody>
					</table>
					<?php
					}
				}
				?>
			</div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
		<script type="text/javascript">
			$('form[name="searchForm"]').submit(function(e){
				var format = /[!@#$%^&*()_+\-=\[\]{};':"\\|.<>\/?]+/;
				var qry = $('input[name="query"]').val();
				if(qry === ''){
					$('#err').show().html('This field is required!');
					e.preventDefault();
				}
				if(format.test(qry)){
					$('#err').show().html('Please enter valid search query, only (,) is allowed!');
					e.preventDefault();
				}
				setTimeout(function(){ $('#err').fadeOut() }, 6000);
			});
		</script>
	</body>
</html>