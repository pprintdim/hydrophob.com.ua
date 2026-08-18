<?php
/**
 * Автогенерація постера з кадру відео (ffmpeg) для медіа-тайлів адмінки.
 * GET ?route=common/video_poster&video=<шлях> — шлях або 'catalog/...' (відносно image/),
 * або легасі 'video/...' (симлінк /video -> image/catalog/video).
 *
 * Постер детермінований: image/catalog/video-posters/<імʼя>-<hash6>.jpg —
 * один файл відео завжди дає один і той самий постер; якщо він уже існує,
 * ffmpeg не запускається (нічого не плодимо).
 */
class ControllerCommonVideoPoster extends Controller {
	public function index() {
		$json = array();

		$video = isset($this->request->get['video']) ? (string)$this->request->get['video'] : '';
		$video = str_replace(array('..', "\0"), '', $video);

		if ($video === '' || strtolower(pathinfo($video, PATHINFO_EXTENSION)) !== 'mp4') {
			$json['error'] = 'bad video';
		} else {
			if (strpos($video, 'video/') === 0) {
				$relative = 'catalog/' . $video; // легасі шлях через симлінк
			} elseif (strpos($video, 'image/') === 0) {
				$relative = substr($video, 6);
			} else {
				$relative = $video; // 'catalog/...'
			}

			$source = DIR_IMAGE . $relative;

			if (!is_file($source)) {
				$json['error'] = 'video not found';
			} else {
				$posterDir = DIR_IMAGE . 'catalog/video-posters/';
				if (!is_dir($posterDir)) {
					mkdir($posterDir, 0755, true);
				}

				$name = pathinfo($relative, PATHINFO_FILENAME) . '-' . substr(md5($relative), 0, 6) . '.webp';
				$poster = $posterDir . $name;
				$posterRel = 'catalog/video-posters/' . $name;

				if (!is_file($poster)) {
					$cmd = 'ffmpeg -y -ss 1 -i ' . escapeshellarg($source) . ' -frames:v 1 -q:v 4 ' . escapeshellarg($poster) . ' 2>/dev/null';
					exec($cmd, $out, $code);

					// відео коротше 1с — беремо перший кадр
					if (!is_file($poster)) {
						exec('ffmpeg -y -i ' . escapeshellarg($source) . ' -frames:v 1 -q:v 4 ' . escapeshellarg($poster) . ' 2>/dev/null');
					}
				}

				if (is_file($poster)) {
					$this->load->model('tool/image');
					$json['poster'] = $posterRel;
					$json['thumb'] = $this->model_tool_image->resize($posterRel, 160, 120);
				} else {
					$json['error'] = 'ffmpeg failed';
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
