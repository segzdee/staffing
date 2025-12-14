<?php

namespace App\Jobs;

use Illuminate\Support\Str;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use App\Helper;
use App\Models\Media;
use App\Models\User;
use App\Models\Updates;
use App\Models\AdminSettings;
use App\Models\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Log;

class EncodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;
    public $tries = 100;
    public $timeout = 9999999;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $video)
    {
       $this->video = $video;
    }

    // /**
    //  * Execute the job.
    //  *
    //  * @return void
    //  */

    public function handleCloudinary(){

      $path = public_path('temp/');
      $videoPathDisk = $path.$this->video->video;

      if (file_exists($videoPathDisk)) {

        $result = cloudinary()->uploadFile($videoPathDisk);
        $securePath = $result->getSecurePath();
        $settings = AdminSettings::first();

        $videoUrl =str_replace(env('CLOUDINARY_WEBURL'), "", $securePath);
        $imageUrl = str_replace($result->getExtension(), "png", $videoUrl);

      // Update name video on Media table
        Media::whereId($this->video->id)->update([
            'video' =>  $videoUrl,
            'encoded' => 'yes',
            'video_poster' => $imageUrl ?? null,
            'uploadedto_aws'=>'N'
        ]);

    // Check if there are other videos that have not been encoded
    $videos = Media::whereUpdatesId($this->video->updates_id)
        ->where('video', '<>', '')
        ->whereEncoded('no')
        ->get();

    if ($videos->count() == 0) {
        // Update date the post and status
        Updates::whereId($this->video->updates_id)->update([
            'date' => now(),
            'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
        ]);

        // Notify to user - destination, author, type, target
        Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);
    }
    // Storage::disk('default')->delete($videoPathDisk);
    $this->moveFileStorage($this->video->video);

    
    }else{
      print( "doesnt exits");
    }


    }

    public function handle()
    {


      // Admin Settings
      $settings = AdminSettings::first();

      // Paths
      $disk = 'default';
      $path = 'temp/';
      $videoPathDisk = $path.$this->video->video;
      Log::debug("videoPathDisk ".file_exists($videoPathDisk));


      $videoPathDiskMp4 = $this->video->id.Str::random(20).uniqid().now()->timestamp.'-converted.mp4';
      $urlWatermark = ucfirst(Helper::urlToDomain(url('/'))).'/'.$this->video->user()->username;
      $font = public_path('webfonts/arial.TTF');
      // $font = 'C\\\\:/Windows/Fonts/arial.ttf';

      // Create Thumbnail Video
        try {
          $videoPoster = Str::random(20).uniqid().now()->timestamp.'-poster.jpg';

          $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
            ->getFrameFromSeconds(1)
            ->export()
          ->toDisk($disk);

          $ffmpeg->save($path.$videoPoster);

          // Clean
          FFMpeg::cleanupTemporaryFiles();

        } catch (\Exception $e) {
          $videoPoster = null;
        }

      // Create a video format...
      $format = new X264();
      $format->setAudioCodec('aac');
      $format->setVideoCodec('libx264');
      $format->setKiloBitrate(0);

      try {
        // open the uploaded video from the right disk...
        if ($settings->watermark_on_videos == 'on') {
          $start=hrtime(true);
          // $ffmpeg = FFMpeg::fromDisk($disk)
          //     ->open($videoPathDisk)
          //     ->addFilter(['-strict', -2])
          //     ->addFilter(function ($filters) use ($urlWatermark, $font) {
          //         $filters->custom("drawtext=text=$urlWatermark:fontfile=$font:x=W-tw-15:y=H-th-15:fontsize=30:fontcolor=white");
          //       })
          //     ->export()
          //     ->toDisk($disk)
          //     ->inFormat($format);

          //   $ffmpeg->save($path.$videoPathDiskMp4);
          //   $end=hrtime(true);
          //   $eta=$end-$start;
          //   Log::debug("Took ".$eta/1e9.' seconds to complete');

            $input_path = public_path($videoPathDisk);
            $output_path = public_path($path.$videoPathDiskMp4);
            $ffmpegCommand = "ffmpeg -loglevel verbose -i \"$input_path\" -vf \"drawtext=text='$urlWatermark':fontfile='$font':fontsize=30:fontcolor=white:x=w-tw-10:y=h-th-10\" -c:v libx264 -c:a aac -preset superfast \"$output_path\"";
            $ffmpegCommand .= " 2>&1";

            exec($ffmpegCommand, $output, $returnVar);
            $end=hrtime(true);
            $eta=$end-$start;
            if ($returnVar === 0) {
              Log::debug("Took ".$eta/1e9.' seconds to complete');
            }
            else {
              Log::error(json_encode($output));
            }

        } else {
          $start=hrtime(true);
          // $ffmpeg = FFMpeg::fromDisk($disk)
          //     ->open($videoPathDisk)
          //     ->addFilter(['-strict', -2])
          //     ->addFilter(function ($filters) use ($urlWatermark, $font) {
          //         $filters->custom("drawtext=text=$urlWatermark:fontfile=$font:x=W-tw-15:y=H-th-15:fontsize=30:fontcolor=white");
          //       })
          //     ->export()
          //     ->toDisk($disk)
          //     ->inFormat($format);

          //   $ffmpeg->save($path.$videoPathDiskMp4);
          //   $end=hrtime(true);
          //   $eta=$end-$start;
          //   Log::debug("Took ".$eta/1e9.' seconds to complete');

            $input_path = public_path($videoPathDisk);
            $output_path = public_path($path.$videoPathDiskMp4);
            $ffmpegCommand = "ffmpeg -loglevel verbose -i \"$input_path\" -vf \"drawtext=text='$urlWatermark':fontfile='$font':fontsize=30:fontcolor=white:x=w-tw-10:y=h-th-10\" -c:v libx264 -c:a aac -preset superfast \"$output_path\"";
            $ffmpegCommand .= " 2>&1";

            exec($ffmpegCommand, $output, $returnVar);
            $end=hrtime(true);
            $eta=$end-$start;
            if ($returnVar === 0) {
              Log::debug("Took ".$eta/1e9.' seconds to complete');
            }
            else {
              Log::error(json_encode($output));
            }
        }

        // Clean
       FFMpeg::cleanupTemporaryFiles();

       // Delete old video
       Storage::disk('default')->delete($videoPathDisk);

          // Update name video on Media table
          Media::whereId($this->video->id)->update([
              'video' => $videoPathDiskMp4,
              'encoded' => 'yes',
              'video_poster' => $videoPoster ?? null
          ]);

          // Check if there are other videos that have not been encoded
          $videos = Media::whereUpdatesId($this->video->updates_id)
              ->where('video', '<>', '')
              ->whereEncoded('no')
              ->get();

              if ($videos->count() == 0) {

                // Update date the post and status
                  Updates::whereId($this->video->updates_id)->update([
                      'date' => now(),
                      'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
                  ]);

                // Notify to user - destination, author, type, target
            		Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);
              }

              // Move Video File to Storage
              $this->moveFileStorage($videoPathDiskMp4);

              // Move Video Poster to Storage
              if ($videoPoster) {
                $this->moveFileStorage($videoPoster);
              }

      } catch (\Exception $e) {

        // Update date the post and status
        $post = Updates::whereId($this->video->updates_id)
          ->whereStatus('encode')
          ->update([
              'date' => now(),
              'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
          ]);

          if ($post) {
            // Notify to user - destination, author, type, target
            Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);

            // Move Video File to Storage
            $this->moveFileStorage($videoPathDiskMp4);

            // Move Video Poster to Storage
            if ($videoPoster) {
              $this->moveFileStorage($videoPoster);
            }
          }
      }

    }// End Handle

    /**
       * Move file to Storage
       *
       * @return void
       */
    protected function moveFileStorage($file)
    {
      $disk = env('FILESYSTEM_DRIVER');
      $path = config('path.videos');
      $localFile = public_path('temp/'.$file);
      

      // // Move the file...
      Storage::disk($disk)->putFileAs($path, new File($localFile), $file);

      Storage::disk($disk)->put( $path.$file, fopen( $localFile, 'r+'));

      // Delete temp file
      unlink($localFile);

   } // end method moveFileStorage
}
