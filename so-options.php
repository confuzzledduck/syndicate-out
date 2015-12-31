<script type="text/javascript">
	function toggleIgnoreServer(groupid, serverid, checkboxStatus) {
		var disabledcolor = '#A0A0A0';
		var serverserverinput = document.getElementById('serverserver-' + groupid + '-' + serverid);
		var serverusernameinput = document.getElementById('serverusername-' + groupid + '-' + serverid);
		var serverpasswordinput = document.getElementById('serverpassword-' + groupid + '-' + serverid);
		if (checkboxStatus == true) {
			serverserverinput.disabled = true; serverserverinput.style.color = disabledcolor;
			serverusernameinput.disabled = true; serverusernameinput.style.color = disabledcolor;
			serverpasswordinput.disabled = true; serverpasswordinput.style.color = disabledcolor;
		} else {
			serverserverinput.disabled = false; serverserverinput.style.color = '';
			serverusernameinput.disabled = false; serverusernameinput.style.color = '';
			serverpasswordinput.disabled = false; serverpasswordinput.style.color = '';
		}
	}
	function toggleTriggerCategory(groupid, triggervalue) {
		if (triggervalue == 'category') {
			document.getElementById('triggercategory-' + groupid).disabled = false;
		} else {
			document.getElementById('triggercategory-' + groupid).disabled = true;
		}
	}
</script>
<div class="wrap">
	<h2>Syndicate Out</h2>
	<form method="post" action="options.php">
	<?php settings_fields( 'syndicate-out-options' ); ?>
		<p>
			<?php _e( 'Each category may be syndicated to as many blogs as required, and a remote blog may appear in as many groups as needed.', 'syndicate-out' ); ?>
			<?php _e( 'Posts which match multiple groups with duplicate servers will only be syndicated to the remote blog once but will use the least restrictive \'transmit categories\' setting.', 'syndicate-out' ); ?>
			<?php _e( 'There is no limit to the number of groups which may be added.', 'syndicate-out' ); ?>
		</p>

<?php
	if ( isset( $syndicateOutOptions['group'] ) && is_array( $syndicateOutOptions['group'] ) ) {
		if ( isset( $_GET['tab'] ) && !isset( $_GET['settings-updated'] ) ) {
			if ( 'newgroup' == $_GET['tab'] ) {
				$syndicateOutOptions['group'][] = array();
				end($syndicateOutOptions['group']);
				$activeTab = key( $syndicateOutOptions['group'] );
				reset( $syndicateOutOptions['group'] );
			} else {
				$activeTab = $_GET['tab'];
			}
		} else {
			$activeTab = 0;
		}
?>
		<h2 class="nav-tab-wrapper">
<?php
		if ( count( $syndicateOutOptions['group'] ) > 0 ) {
			foreach ( $syndicateOutOptions['group'] AS $groupKey => $syndicationGroup ) {
				if ( isset( $syndicationGroup['name'] ) && ( null != $syndicationGroup['name'] ) ) {
					$groupName = htmlentities2( $syndicationGroup['name'] );
				} else {
					$groupName = sprintf( __( 'Syndication Group %s', 'syndicate-out' ), number_format_i18n( ( $groupKey + 1 ) ) );
				}
?>
			<a href="?page=syndicate_out&tab=<?php echo $groupKey; ?>" class="nav-tab <?php echo ( $activeTab == $groupKey ) ? 'nav-tab-active' : ''; ?>"><?php echo $groupName; ?></a>
<?php
			}
		} else {
?>
			<a href="?page=syndicate_out&tab=0" class="nav-tab nav-tab-active"><?php _e( 'Syndication Group 1', 'syndicate-out' ); ?></a>
<?php
		}
?>
			<a href="?page=syndicate_out&tab=newgroup" class="nav-tab"><?php _e( '+', 'syndicate-out' ); ?></a>
		</h2>
		
<?php
		//foreach ( $syndicateOutOptions['group'] AS $groupKey => $syndicationGroup ) {
		$groupKey = $activeTab;
		$syndicationGroup = $syndicateOutOptions['group'][$activeTab];
		$additionalRows = 0;
		if ( is_array( $newServerRows ) && count( $newServerRows ) > 0 ) {
			if ( array_key_exists( $groupKey, $newServerRows ) && $newServerRows[$groupKey] > 0 ) {
				$additionalRows = $newServerRows[$groupKey];
			}
		}
?>
		<div style="padding-bottom: 15px;">
			
			<table class="form-table">
				<tbody>
					<tr>
					   <th scope="row"><?php _e( 'Group Name', 'syndicate-out' ); ?></th>
						 <td><input id="groupname-<?php echo $groupKey; ?>" style="width: 260px;" type="text" name="so_options[group][<?php echo $groupKey ?>][name]" value="<?php echo $groupName; ?>" /></tr>
					<tr>
					   <th scope="row"><?php _e( 'Syndicate', 'syndicate-out' ); ?></th>
					   <td>
							<select id="triggermethod-<?php echo $groupKey; ?>" name="so_options[group][<?php echo $groupKey ?>][trigger]" onchange="toggleTriggerCategory(<?php echo $groupKey ?>, this.value);">
								<option value="disable"<?php echo ( isset( $syndicationGroup['category'] ) && ( 'none' == $syndicationGroup['category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Disabled', 'syndicate-out' ); ?></option>
								<option value="all"<?php echo ( isset( $syndicationGroup['category'] ) && ( -1 == $syndicationGroup['category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'All posts', 'syndicate-out' ); ?></option>
								<option value="post"<?php echo ( isset( $syndicationGroup['category'] ) && ( -2 == $syndicationGroup['category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Selected posts', 'syndicate-out' ); ?></option>
								<option value="category"<?php echo ( isset( $syndicationGroup['category'] ) && ( 0 < $syndicationGroup['category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Selected category', 'syndicate-out' ); ?></option>
							</select>
							<select id="triggercategory-<?php echo $groupKey; ?>" name="so_options[group][<?php echo $groupKey ?>][category]" <?php if ( isset( $syndicationGroup['category'] ) && ( 0 > $syndicationGroup['category'] || 'none' == $syndicationGroup['category'] ) ) { echo 'disabled="true"'; } ?>>
								<option value="-1"><?php _e( 'Select category', 'syndicate-out' ); ?></option>
<?php
		foreach ( get_categories( array ( 'hide_empty' => 0 ) ) AS $blogCategory ) {
			echo '<option value="'.$blogCategory->cat_ID.'"'.( ( isset( $syndicationGroup['category'] ) && ( $syndicationGroup['category'] == $blogCategory->cat_ID ) ) ? ' selected="selected"' : '' ).'>'.$blogCategory->cat_name.'</option>';
		}
?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Transmit categories', 'syndicate-out' ); ?></th>
						<td>
							<select id="transmitcategories-<?php echo $groupKey; ?>" name="so_options[group][<?php echo $groupKey ?>][syndicate_category]">
								<option value="none"<?php echo ( isset( $syndicationGroup['syndicate_category'] ) && ( 'none' == $syndicationGroup['syndicate_category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'No categories', 'syndicate-out' ); ?></option>
								<option value="all"<?php echo ( isset( $syndicationGroup['syndicate_category'] ) && ( 'all' == $syndicationGroup['syndicate_category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'All post categories', 'syndicate-out' ); ?></option>
								<option value="syndication"<?php echo ( isset( $syndicationGroup['syndicate_category'] ) && ( 'allbut' == $syndicationGroup['syndicate_category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'All except syndication category', 'syndicate-out' ); ?></option>
								<option value="syndication"<?php echo ( isset( $syndicationGroup['syndicate_category'] ) && ( 'syndication' == $syndicationGroup['syndicate_category'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Syndication category only', 'syndicate-out' ); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Featured images', 'syndicate-out' ); ?></th>
						<td>
							<select id="featuredimages-<?php echo $groupKey; ?>" name="so_options[group][<?php echo $groupKey ?>][featured_image]">
								<option value="true"<?php echo ( isset( $syndicationGroup['featured_image'] ) && ( true == $syndicationGroup['featured_image'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Transmit featured images', 'syndicate-out' ); ?></option>
								<option value="false"<?php echo ( isset( $syndicationGroup['featured_image'] ) && ( false == $syndicationGroup['featured_image'] ) ) ? ' selected="selected"' : ''; ?>><?php _e( 'Do not transmit featured images', 'syndicate-out' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			
			<br />
			<table class="widefat">
				<thead>
				<tr valign="top">
					<th scope="column" class="manage-column check-column"></th>
					<th scope="column" class="manage-column"><?php _e( 'Address', 'syndicate-out' ); ?></th>
					<th scope="column" class="manage-column"><?php _e( 'Username', 'syndicate-out' ); ?></th>
					<th scope="column" class="manage-column"><?php _e( 'Password', 'syndicate-out' ); ?></th>
					<th scope="column" class="manage-column"><?php _e( 'Status', 'syndicate-out' ); ?></th>
				</tr>
				</thead>
				<tbody>
<?php
		if ( ! isset( $syndicationGroup['servers'] ) || count( $syndicationGroup['servers'] ) == 0 || isset( $_POST['addrow'][$groupKey] ) ) {
			$syndicationGroup['servers'][] = array( 'server' => '', 'username' => '', 'password' => '' );
		}
		if ( $additionalRows > 0 ) {
			for ($i = 0; $i < $additionalRows; $i++) {
				$syndicationGroup['servers'][] = array( 'server' => '', 'username' => '', 'password' => '' );
			}
		}
		foreach ( $syndicationGroup['servers'] AS $serverKey => $soServer ) {
?>
					<tr id="serverrow-<?php echo $serverKey ?>-<?php echo $groupKey ?>">
						<th scope="row" class="check-column"><input type="checkbox" name="so_options[group][<?php echo $groupKey ?>][servers][<?php echo htmlentities2( $serverKey ); ?>][delete]" value="1" onclick="toggleIgnoreServer(<?php echo $serverKey ?>, <?php echo $groupKey ?>, this.checked);" /></th>
						<td><input id="serverserver-<?php echo $serverKey ?>-<?php echo $groupKey ?>" style="width: 260px;" type="text" name="so_options[group][<?php echo $groupKey ?>][servers][<?php echo htmlentities2( $serverKey ); ?>][server]" value="<?php echo htmlentities2( $soServer['server'] ); ?>" /></td>
						<td><input id="serverusername-<?php echo $serverKey ?>-<?php echo $groupKey ?>" type="text" name="so_options[group][<?php echo $groupKey ?>][servers][<?php echo htmlentities2( $serverKey ); ?>][username]" value="<?php echo htmlentities2( $soServer['username'] ); ?>" /></td>
						<td><input id="serverpassword-<?php echo $serverKey ?>-<?php echo $groupKey ?>" type="password" name="so_options[group][<?php echo $groupKey ?>][servers][<?php echo htmlentities2( $serverKey ); ?>][password]" value="<?php echo htmlentities2( $soServer['password'] ); ?>" /></td>
						<td>
							<?php _e( 'Authentication', 'syndicate-out' ); ?>: <?php echo ( isset( $soServer['authenticated'] ) && ( true == $soServer['authenticated'] ) ) ? '<span style="color: #006505;">'.__( 'OK', 'syndicate-out' ).'</span>' : '<span style="color: #BC0B0B;">'.__( 'Failed', 'syndicate-out' ).'</span>'; ?>.<br />
							<?php echo ( isset( $soServer['authenticated'] ) && ( true == $soServer['authenticated'] ) ) ? ( __( 'Remote API', 'syndicate-out' ).': ' ) : (''); ?><span style="color: <?php echo ( isset( $soServer['authenticated'] ) && ( true == $soServer['authenticated'] ) ) ? '#006505' : '#BC0B0B'; ?>;"><?php echo htmlentities2( $soServer['api'] ); ?></span>.
						</td>
					</tr>
<?php
		}
?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<div class="alignleft actions">
					<?php printf( __( 'Add %s new server(s) into group', 'syndicate-out' ), '<input type="text" size="3" value="1" name="so_options[group]['.$groupKey.'][addrow]" />' ); ?>
					<input type="submit" name="so_options[group][<?php echo $groupKey; ?>][addrowbutton]" value="<?php _e( 'Go', 'syndicate-out' ); ?>" class="button" />
				</div>
				<div class="alignleft actions">
					<input type="submit" name="so_options[deletegroup][<?php echo $groupKey; ?>]" value="<?php _e( 'Delete group', 'syndicate-out' ); ?>" class="button delete" />
					<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save group', 'syndicate-out' ) ?>" />
				</div>
				<div class="tablenav-pages one-page">
					<span class="displaying-num">
						<?php printf( _n( '1 server', '%s servers', count( $syndicationGroup['servers'] ), 'syndicate-out' ), number_format_i18n( count( $syndicationGroup['servers'] ) ) ); ?>
					</span>
				</div>
			</div>
			
		</div>
<?php
		//}
	}
?>
	</form>
</div>
