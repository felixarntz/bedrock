<?php
$style = array();
$style['body'] = "background-color: " . $data['background_color'] . ";font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;";
$style['wrapper'] = "width:100%;-webkit-text-size-adjust:none !important;margin:0;padding: 70px 0 70px 0;";
$style['container'] = "box-shadow:0 0 0 1px #f3f3f3 !important;border-radius:3px !important;background-color: #ffffff;border: 1px solid #e9e9e9;border-radius:3px !important;padding: 20px;";
$style['header'] = "color: #00000;border-top-left-radius:3px !important;border-top-right-radius:3px !important;border-bottom: 0;font-weight:bold;line-height:100%;text-align: center;vertical-align:middle;";
$style['headline'] = "color: #000000;margin:0;padding: 28px 24px;display:block;font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;font-size:32px;font-weight: 500;line-height: 1.2;";
$style['main'] = "border-radius:3px !important;font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;";
$style['main_inner'] = "color: #000000;font-size:14px;font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;line-height:150%;text-align:left;";
$style['footer'] = " border-top:0; -webkit-border-radius:3px; ";
$style['credit'] = "border:0; color: #000000; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size:12px; line-height:125%; text-align:center; ";
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo $data['title']; ?></title>
		<?php if ( $data['styles'] ) : ?>
			<style type="text/css"><?php echo $data['styles']; ?></style>
		<?php endif; ?>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="<?php echo $style['body']; ?>">
		<div style="<?php echo $style['wrapper']; ?>">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
				<tr>
					<td align="center" valign="top">
						<?php if ( $data['header_image'] ) : ?>
							<div id="header_image">
								<p style="margin-top:0;"><img src="<?php echo esc_url( $data['header_image'] ); ?>" alt="<?php echo $data['headline']; ?>" /></p>
							</div>
						<?php endif; ?>
						<table border="0" cellpadding="0" cellspacing="0" width="520" id="container" style="<?php echo $style['container']; ?>">
							<?php if ( $data['headline'] ) : ?>
								<tr>
									<td align="center" valign="top">
										<table border="0" cellpadding="0" cellspacing="0" width="520" id="header" style="<?php echo $style['header']; ?>" bgcolor="#ffffff">
											<tr>
												<td>
													<h1 style="<?php echo $style['headline']; ?>"><?php echo $data['headline']; ?></h1>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( $data['main_content'] ) : ?>
								<tr>
									<td align="center" valign="top">
										<table border="0" cellpadding="0" cellspacing="0" width="520" id="main">
											<tr>
												<td valign="top" style="<?php echo $style['main']; ?>">
													<table border="0" cellpadding="20" cellspacing="0" width="100%">
														<tr>
															<td valign="top">
																<div style="<?php echo $style['main_inner']; ?>">
																	<?php echo $data['main_content']; ?>
																</div>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( $data['footer_content'] ) : ?>
								<tr>
									<td align="center" valign="top">
										<table border="0" cellpadding="10" cellspacing="0" width="600" id="footer" style="<?php echo $style['footer']; ?>">
											<tr>
												<td valign="top">
													<table border="0" cellpadding="10" cellspacing="0" width="100%">
														<tr>
															<td colspan="2" valign="middle" id="credit" style="<?php echo $style['credit']; ?>">
																<?php echo $data['footer_content']; ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
