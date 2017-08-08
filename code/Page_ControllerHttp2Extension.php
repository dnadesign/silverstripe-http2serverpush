<?php

/**
 * Class Page_ControllerHttp2Extension
 *
 * Cookie-cached implementation of HTTP2 server push.
 * Based loosely on https://css-tricks.com/cache-aware-server-push/
 *
 */
class Page_ControllerHttp2Extension extends Extension
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

                    foreach ($versionedPushes as $id => $asset) {

                        // Collate assets which are new or have changed
                        if (!array_key_exists($id, $cookiePushes)
                            || $asset['hash'] !== $cookiePushes[$id]['hash']) {
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
        $themeURL = Director::baseURL() . SSViewer::get_theme_folder();
        $pushString = '';

        foreach ($versionedPushes as $asset) {
            $pushString .= '<' . $themeURL . $asset['link'] . '>; rel=preload; as=' . $asset['type'];
            if ($asset !== end($versionedPushes)) {
                $pushString .= ", ";
            }
        }
        return $pushString;
    }

    public function createVersionedList($pushes)
    {
        $themePath = BASE_PATH . '/' . SSViewer::get_theme_folder();
        $versionedPushes = [];

        // TODO: Look into using the hash as the array key
        foreach ($pushes as $type => $links) {
            foreach ($links as $link) {
                if (is_file($themePath . $link)) {
                    array_push($versionedPushes, array(
                            'type' => $type,
                            'link' => $link,
                            'hash' => substr(md5_file($themePath . $link), 0, 8)
                        )
                    );
                }
            }
        }

        return $versionedPushes;
    }

}