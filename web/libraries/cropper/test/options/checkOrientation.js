$(function () {

  'use strict';

  QUnit.test('options#checkOrientation: true', function (assert) {
    var done = assert.async();
    var $image = $(window.createCropperImage({
          src: '../assets/img/picture-3.jpg'
        }));

    $image.cropper({
      built: function () {
        var data = $(this).cropper('getData');

        assert.notEqual(data.rotate, 0);
        done();
      }
    });
  });

  QUnit.test('options#checkOrientation: false', function (assert) {
    var done = assert.async();
    var $image = $(window.createCropperImage({
          src: '../assets/img/picture-3.jpg'
        }));

    $image.cropper({
      checkOrientation: false,

      built: function () {
        var data = $(this).cropper('getData');

        assert.equal(data.rotate, 0);
        done();
      }
    });
  });

});
