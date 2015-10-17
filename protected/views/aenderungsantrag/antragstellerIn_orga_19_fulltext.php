<?php

/**
 * @var AenderungsantraegeController $this
 * @var string $mode
 * @var Aenderungsantrag $antrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 */


if ($mode == "neu") {
	?>
	<div class="policy_antragstellerIn_orga_5">
		<h3><?= $sprache->get("AntragstellerIn") ?></h3>
		<br>

		<?php
		echo $veranstaltung->getPolicyAenderungsantraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
		?>

		<div class="control-group" id="Person_typ_chooser">
			<label class="control-label">Ich bin...</label>

			<div class="controls">
				<label><input type="radio" name="Person[typ]" value="mitglied" required checked> Parteimitglied</label><br>
				<label><input type="radio" name="Person[typ]" value="organisation" required> Gremium (BAG, LDK)</label><br>
			</div>
		</div>

		<div class="control-group" id="Organisation_Beschlussdatum_holder">
			<label class="control-label">Beschlussdatum:</label>

			<div class="controls">
				<input type="text" name="Organisation_Beschlussdatum" placeholder="TT.MM.JJJJ" id="Organisation_Beschlussdatum">
			</div>
		</div>

		<div class="control-group" id="UnterstuetzerInnen">
			<label class="control-label">
				Min. 19 Weitere<br>AntragstellerInnen<br>
				<br>
				<a href="#" class="fulltext_opener"><span class="icon icon-arrow-right"></span> Volltextfeld</a>
				<a href="#" class="fulltext_closer" style="display: none;"><span class="icon icon-arrow-right"></span> Volltextfeld ausblenden</a>
			</label>

			<div class="controle unterstuetzerInnen_list_fulltext_holder" style="display: none; float: left; padding-left: 20px;">
				<textarea name="UnterstuetzerInnen_fulltext" rows="10" cols="60" style="min-width: 300px;"></textarea>
			</div>

			<div class="controls unterstuetzerInnen_list">
				<?php for ($i = 0; $i < 19; $i++) { ?>
					<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">
					<br>
				<?php } ?>
			</div>
			<div class="unterstuetzerInnen_adder">
				<a href="#"><span class="icon icon-plus"></span> UnterstützerIn hinzufügen</a>
			</div>
		</div>
	</div>

	<script>
		$(function () {
			var $chooser = $("#Person_typ_chooser"),
				$unter = $("#UnterstuetzerInnen"),
				$andereAntragstellerIn = $("input[name=andere_antragstellerIn]"),
				$beschlussdatum = $("#Organisation_Beschlussdatum_holder");

			$chooser.find("input").change(function () {
				var val = $chooser.find("input:checked").val();
				if (val == "mitglied") {
					$unter.show();
					$unter.find("input[type=text]").prop("required", true);
					$beschlussdatum.hide();
					$beschlussdatum.find("input").removeAttr("required");
				}
				if (val == "organisation") {
					$unter.hide();
					$unter.find("input[type=text]").prop("required", false);
					$beschlussdatum.show();
					$beschlussdatum.find("input").attr("required", "required");
				}
			}).change();

			if ($andereAntragstellerIn.length > 0) $andereAntragstellerIn.change(function () {
				if ($(this).prop("checked")) {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.data("orig", $input.val());
						$input.val("");
					});
				} else {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.val($input.data("orig"));
					});
				}
			}).change();
			$unter.find(".unterstuetzerInnen_adder a").click(function(ev) {
				ev.preventDefault();
				$(".unterstuetzerInnen_list").append('<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">\
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">\
					<br>');
			});
			$unter.find(".fulltext_opener").click(function(ev) {
				ev.preventDefault();
				$unter.find(".unterstuetzerInnen_list").hide();
				$unter.find(".unterstuetzerInnen_list").find("input").removeAttr("required");
				$unter.find(".unterstuetzerInnen_adder").hide();
				$unter.find(".unterstuetzerInnen_list_fulltext_holder").show();
				$unter.find(".fulltext_closer").show();
				$unter.find(".fulltext_opener").hide();
			});
			$unter.find(".fulltext_closer").click(function(ev) {
				ev.preventDefault();
				$unter.find(".unterstuetzerInnen_list").show();
				$unter.find(".unterstuetzerInnen_list").find("input").attr("required", "required");
				$unter.find(".unterstuetzerInnen_adder").show();
				$unter.find(".unterstuetzerInnen_list_fulltext_holder").hide();
				$unter.find(".unterstuetzerInnen_list_fulltext_holder textarea").val("");
				$unter.find(".fulltext_closer").hide();
				$unter.find(".fulltext_opener").show();
			});

			$("#antrag_stellen_form").submit(function(ev) {
				var person_typ = $chooser.find("input:checked").val();
				if (person_typ == "organisation") {
					var datum = $beschlussdatum.find("input").val();
					if (!datum.match(/^[0-9]{2}\. *[0-9]{2}\. *[0-9]{4}$/)) {
						ev.preventDefault();
						alert("Bitte gib das Datum der Beschlussfassung im Format TT.MM.JJJJ (z.B. 24.12.2013).");
						$beschlussdatum.find("input").focus();
					}
				}
			});
		});
	</script>

	<div class="ae_select_confirm">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => $sprache->get("Änderungsantrag stellen"))); ?>
	</div>

	<br><br>

<?php
}
