/*!
 * Piwik - free/libre analytics platform
 *
 * Screenshot tests for Google Authenticator plugin
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("GoogleAuthenticator", function () {
    this.timeout(0);

    it('should load start settings page', function (done) {
        expect.screenshot('disabled').to.be.captureSelector('#content', function (page) {
            page.load("?module=GoogleAuthenticator&action=settings&idSite=1&period=day&date=yesterday");
        }, done);
    });

    it('should load setup settings page', function (done) {
        expect.screenshot('setup').to.be.captureSelector('#content', function (page) {
            page.click("#content .btn-lg");
            page.evaluate(function(){
                $('#gasecret').attr('value', '3UH6WHFP3DWZNYQBGSCO7GU5CRGR74U7');
                $('#qrcode').css({visibility: 'hidden'});
            });
        }, done);
    });

    
});