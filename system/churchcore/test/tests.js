QUnit.test( "hello test", function( assert ) {
  assert.ok( 1 == "1", "Passed!" );
});


QUnit.test( "Basic date operations", function (assert) {
  assert.deepEqual(new Date('2014-01-02 12:10').toStringEn(true), "2014-01-02 12:10");
  //assert.deepEqual(new Date('2014-01-02 12:01').toStringEn(true), "2014-01-02 12:01");
  assert.deepEqual(new Date('2014-01-02 12:10').toStringEn(true).toDateEn(true), new Date('2014-01-02 12:10'));
});

var cal_single = new CCEvent({
      startdate : new Date('2014-01-09 10:00'),
      enddate   : new Date('2014-01-09 11:00'),
      repeat_id : 0
    });
var cal_series = new CCEvent({
      startdate : new Date('2014-01-01 10:00'),
      enddate   : new Date('2014-01-01 11:00'),
      repeat_id : 1,
      repeat_until : new Date('2014-01-10 00:00')
    });
var cal_series_2nd = new CCEvent({
      startdate : new Date('2014-01-09 10:00'),
      enddate   : new Date('2014-01-09 11:00'),
      repeat_id : 1,
      repeat_until : new Date('2014-01-10 00:00')
    });
var cal_series_with_exception = new CCEvent({
      startdate : new Date('2014-01-01 10:00'),
      enddate   : new Date('2014-01-01 11:00'),
      repeat_id : 1,
      repeat_until : new Date('2014-01-10 00:00'),
      exceptionids : -1,
        exceptions: {
          "-1" : {
                    "except_date_end": "2014-01-09",
                    "except_date_start": "2014-01-09",
                    "id": -1
                  }
        }
    });

QUnit.test( "Test Event Split", function( assert ) {
  //assert.deepEqual(cal_single, cal_single.clone(), "Check Clone single");
  //assert.deepEqual(cal_series, cloneEvent(cal_series), "Check Clone series");

  // Test Single Events
  cal_single.doSplit( new Date('2014-01-09 10:00'), false, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(pastEvent, null, "pastEvent: Test one day in single");
  });
  cal_single.doSplit(new Date('2014-01-09 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(newEvent, newEvent, "pastEvent: Test one day in single");
  });
  cal_single.doSplit( new Date('2014-01-09 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(pastEvent, null, "pastEvent: Test one day in single");
  });

  // Test One Day in series
  cal_series.doSplit(new Date('2014-01-09 10:00'), false, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(pastEvent, cal_series_with_exception.clone(), "pastEvent: Test one day in series");
  });
  cal_series.doSplit(new Date('2014-01-09 10:00'), false, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(newEvent, cal_single.clone(), "NewEvent: Test one day in series");
  });

  // Test rest of series
  var d = cal_series.clone(); d.repeat_until = new Date('2014-01-08 00:00');
  cal_series.doSplit(new Date('2014-01-09 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(pastEvent, d, "pastEvent: Test rest of series");
  });
  cal_series.doSplit(new Date('2014-01-09 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(newEvent, cal_series_2nd.clone(), "Test rest of series");
  });

  // Test special case first element edited!
  cal_series.doSplit(new Date('2014-01-01 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(pastEvent, null, "pastEvent: Test special case: first element edited!");
  });
  cal_series.doSplit(new Date('2014-01-01 10:00'), true, function(newEvent, pastEvent, splitDate) {
    assert.deepEqual(newEvent, newEvent, "newEvent: Test special case: first element edited!");
  });
});

$(document).ready(function() {
  debug = true;
  test = true;
});
