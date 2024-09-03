<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Brick\DateTime\DefaultClock;
use Brick\DateTime\ZonedDateTime;
use Brick\DateTime\Duration;
use Brick\DateTime\TimeZone;
use Meraki\Html\Calendar;
use Meraki\Html\Attribute;

$clock = DefaultClock::get();

$newYearsDay = ZonedDateTime::parse('2024-01-01T08:00:00+10:00[Australia/Sydney]');
$valentinesDay = ZonedDateTime::parse('2024-02-14T08:00:00+10:00[Australia/Sydney]');
$independenceDay = ZonedDateTime::parse('2024-07-04T08:00:00+10:00[Australia/Sydney]');
$christmasDay = ZonedDateTime::parse('2024-12-25T08:00:00+10:00[Australia/Sydney]');
$oneDay = Duration::ofDays(1);
$holidays = new Calendar\Source\InMemory(
	hash('sha256', 'Holidays'),
	'Holidays',
	'178',
	[
		new Calendar\Event('1', 'New Year\'s Day', $newYearsDay, $oneDay),
		new Calendar\Event('2', 'Valentine\'s Day', $valentinesDay, $oneDay),
		new Calendar\Event('3', 'Independence Day', $independenceDay, Duration::ofMinutes(15)),
		new Calendar\Event('4', 'Christmas Day', $christmasDay, $oneDay),
	]
);

$birthdays = new Calendar\Source\InMemory(
	hash('sha256', 'Birthdays'),
	'Birthdays',
	'280',
	[
		new Calendar\Event('1', 'test birthday', ZonedDateTime::parse('2024-08-23T13:15+10:00[Australia/Sydney]'), Duration::ofMinutes(30)),
	]
);

$work = new Calendar\Source\InMemory(
	hash('sha256', 'Work'),
	'Work',
	'0',
	[
		new Calendar\Event('1', 'car lesson with me', ZonedDateTime::parse('2024-08-27T10:15+10:00[Australia/Sydney]'), Duration::ofMinutes(45)),
	]
);

$attrs = new Attribute\Set();
$attrs->add(new Attribute\TimeZone('Australia/Sydney'));

$sources = new Calendar\Source\Set($holidays, $birthdays, $work);
$calendar = new Calendar($clock, $sources, $attrs);

$calendar->appendContent(
	new Calendar\Widget\MiniMonth(),
	new Calendar\Widget\SourceSelector(),
	new Calendar\Widget\ViewSelector(),
	new Calendar\Widget\Navigation(),
	new Calendar\Widget\DateDisplay(),
);

echo <<<HTML
<style>
.calendar {
	display: grid;
	grid-template-columns: 1fr auto 1fr auto;
	grid-template-rows: auto auto auto;
	gap: 1rem;
	grid-template-areas:
		"title title title title"
		"mini-month view-selector date-display navigation"
		"source-selector view view view";
}

.calendar .mini-month {
	display: grid;
	grid-template-columns: auto;
	grid-template-rows: auto auto auto;
	grid-template-areas:
		"year-month"
		"day-names"
		"days";
	gap: .75rem;
}

.calendar .mini-month .year-month {
	grid-area: year-month;
	display: flex;
	flex-direction: row-reverse;	/* swap year and month around */
	justify-content: center;
	gap: .25rem;
}
.calendar .mini-month .day-names {
	grid-area: day-names;
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	cursor: default;
	list-style: none;
	padding: 0;
	margin: 0;
}
.calendar .mini-month .days {
	grid-area: days;
	display: grid;
	grid-template-columns: repeat(7, 1fr);
}
.calendar .mini-month .days [data-today] {
	cursor: pointer;
	background-color: red;
}

.calendar .view {
	grid-area: view;
	display: grid;
  	grid-template-columns: auto repeat(7, 1fr);
}

.calendar [data-now] {
	background-color: lightblue;
}

.calendar .event {
	background-color: var(--calendar-source-colour);
}
.calendar .header-cell {
  border-bottom: 1px solid gray;
  display: grid;
  justify-content: center;
  text-align: center;
}
.calendar :where(.time-column, .day-column) + :where(.time-column, .day-column) {
	border-left: 1px solid grey;
}
.calendar .view {
	border: 1px solid grey;
	border-bottom: none;
}
.calendar :where(.time-column, .day-column) :where(.body-cell, .empty-cell) {
  border-bottom: 1px solid gray;
}

.calendar .navigation {
	display: flex;
	justify-content: center;
	gap: 1rem;
}

.calendar button.event {
	background-color: hsl(var(--calendar-source-colour) 100% 95%);
	border: none;
	border-left: 1px solid hsl(var(--calendar-source-colour) 100% 35%);
	cursor: pointer;
	padding: .5rem;
}

.calendar .source-selector li > label {
	color: hsl(var(--calendar-source-colour) 80% 35%);
}

.calendar .source-selector li > input {
	accent-color: hsl(var(--calendar-source-colour) 100% 95%);
}
</style>
HTML;
echo $calendar->render();
