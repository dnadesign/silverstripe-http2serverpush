<?php

namespace DNADesign\Http2Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Cookie;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Class PageControllerHttp2Extension
 *
 * Cookie-cached implementation of HTTP2 server push.
 * Based loosely on https://css-tricks.com/cache-aware-server-push/
 *
 */
class PageControllerHttp2Extension extends Extension
{

    public function onAfterInit()
    {
        $pushes = Config::inst()->get('Page', 'server_push');

        if ($pushes) {

            $versionedPushes = $this->createVersionedList($pushes);

            if (!Cookie::get('pushedAssets')) {

                // We haven't pushed these assets to this client before so we do that now.

                $headerPushString = $this->getPushString($versionedPushes);
                $this->owner->response->addHeader("Link", $headerPushString);

                Cookie::set('pushedAssets', json_encode($versionedPushes), 2592000, "/");

            } else {

                // We already pushed some assets before, so we see if we need to push new ones or not.

                $cookiePushes = json_decode(Cookie::get('pushedAssets'), true);

                if ($versionedPushes !== $cookiePushes) {

                    $diffPushes = [];

                    foreach ($versionedPushes as $versionedId => $asset) {

                        // Collate assets which are new or have changed
                        if (!array_key_exists($versionedId, $cookiePushes)) {
                            array_push($diffPushes, $asset);
                        }
                    }

                    // Build and add a push header using only the changed files
                    $headerPushString = $this->getPushString($diffPushes);
                    $this->owner->response->addHeader("Link", $headerPushString);

                    // Set the cookie with the updated full list of pushed assets
                    Cookie::set('pushedAssets', json_encode($versionedPushes), 2592000, "/");
                }
            }
        }
    }

    public function getPushString($versionedPushes)
    {
        $themes = SSViewer::get_themes();

        $pushString = '';

        foreach ($versionedPushes as $asset) {

            $themesFilePath = ThemeResourceLoader::inst()->findThemedResource($asset['link'], $themes);

            $pushString .= '<' . 'resources/' . $themesFilePath . '>; rel=preload; as=' . $asset['type'];
            if ($asset !== end($versionedPushes)) {
                $pushString .= ", ";
            }
        }
        return $pushString;
    }

    public function createVersionedList($pushes)
    {
        $themes = SSViewer::get_themes();
        $versionedPushes = [];

        foreach ($pushes as $type => $links) {
            foreach ($links as $link) {

                $themesFilePath = ThemeResourceLoader::inst()->findThemedResource($link, $themes);

                if (is_file('resources/' . $themesFilePath)) {
                    $versionedId = substr(md5_file('resources/' . $themesFilePath), 0, 8);
                    $versionedPushes[$versionedId] = array(
                        'type' => $type,
                        'link' => $link
                    );
                }
            }
        }

        return $versionedPushes;
    }
}
