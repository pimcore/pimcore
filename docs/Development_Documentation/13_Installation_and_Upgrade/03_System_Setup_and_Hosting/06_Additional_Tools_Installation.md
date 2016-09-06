# FFMPEG Installation 

## Install FFMPEG and qt-faststart (on Linux)

Run all this commands as root

```bash
cd ~
wget http://FFMPEG-ARCHIVE-URL-FROM-ABOVE -O ffmpeg.tar.xz
tar -Jxf ffmpeg*.tar.xz
rm ffmpeg*.tar.xz
mv ffmpeg-* /usr/local/ffmpeg
ln -s /usr/local/ffmpeg/ffmpeg /usr/local/bin/
ln -s /usr/local/ffmpeg/ffprobe /usr/local/bin/
ln -s /usr/local/ffmpeg/qt-faststart /usr/local/bin/
ln -s /usr/local/ffmpeg/qt-faststart /usr/local/bin/qtfaststart
```

That's it! The ffmpeg binary is now in here: ```/usr/local/bin/ffmpeg```
 
 
## Check if FFMPEG is detected correctly
Open ```Tools``` -> ```System Info``` -> ```System Requirements Check``` in Pimcore backend and check if 
FFMPEG is detected correctly. 

![FFMPEG Installation](../../img/ffmpeg1.png)

If not: ensure ```/usr/local/bin``` is in your ```$PATH``` environment variable, or add i to the Pimcore system 
settings (section ```general```) or to your environment variable.

![FFMPEG Path](../../img/ffmpeg2.png)