import json
import sys
import inspect
from time import time, gmtime, strftime
import time
from math import floor
import re

debug = False

tempDir = sys.argv[1]
logFile = sys.argv[2]

def lineno():
    #Returns the current line number in our program.
	#(http://code.activestate.com/recipes/145297-grabbing-the-current-line-number-easily/)
    return inspect.currentframe().f_back.f_lineno

def log(type, message):
	log = open(logFile,'a');
	logTime = time.time()
	logTime = strftime("%Y-%m-%dT%H:%M:%S", gmtime(logTime))+str(logTime-floor(logTime)).replace('0','',1)+"+00:00"
	json.dump({"language":"python","type":type,"message":message,"eventTime":logTime,"logTime":logTime,"file":sys.argv[0],"line":str(inspect.currentframe().f_back.f_lineno)}, log, indent=None, separators=(',',':'))
	log.write("\n");
	log.close()

def progress(percent):
	log = open(logFile,'a');
	logTime = time.time()
	logTime = strftime("%Y-%m-%dT%H:%M:%S", gmtime(logTime))+str(logTime-floor(logTime)).replace('0','',1)+"+00:00"
	json.dump({"language":"python","type":"PROGRESS","message":"prepare.py:"+str(percent)+"%","eventTime":logTime,"logTime":logTime,"file":sys.argv[0],"line":str(inspect.currentframe().f_back.f_lineno)}, log, indent=None, separators=(',',':'))
	log.write("\n");
	log.close()

progress(0)

def hasAttribute(elem, attr):
	if (not isinstance(attr, str) and not isinstance(attr, unicode)):
		return False
	elif (not isinstance(elem, list)):
		return False
	elif (len(elem) == 1 or not isinstance(elem[1], dict)):
		return False
	elif (not attr in elem[1]):
		return False
	else:
		return True

def getAttribute(elem, attr, default):
	if (hasAttribute(elem, attr)):
		return elem[1][attr]
	else:
		return default

def setAttribute(elem, attr, value):
	global debug
	if ((isinstance(attr, str) or isinstance(attr, unicode)) and isinstance(elem, list) and len(elem) >= 1):
		if (len(elem) == 1):
			elem.append(dict({attr:value}))
		else:
			if (isinstance(elem[1], dict)):
				elem[1][attr] = value
			else:
				elem.insert(1,dict({attr:value}))
		return True
	else:
		return False

def removeAttribute(elem, attr):
	if (len(elem) > 0 and isinstance(elem[1], dict)):
		del elem[1][attr]

def numberOfChildren(elem):
	if (len(elem) > 1 and isinstance(elem[1], dict)):
		return len(elem)-2
	else:
		return len(elem)-1

def getChild(elem, nr):
	if (len(elem) > 1 and isinstance(elem[1], dict)):
		return elem[nr+2]
	else:
		return elem[nr+1]

def textContent(elem):
	text = ''
	if (isinstance(elem, list)):
		for i in range(1,len(elem)):
			if (isinstance(elem[i], str) or isinstance(elem[i], unicode)):
				text += elem[i]
			elif (isinstance(elem[i], list)):
				text += textContent(elem[i])
	return text

def determineMimeType(elem):
	# seq and par doesn't have an intrinsic type
	if (elem[0] == 's' or elem[0] == 'p'):
		return
	
	# if element already has a type; return
	if (len(getAttribute(elem,'t','')) > 0):
		return
	
	# try to determine MIME type from filename extension
	ext = getAttribute(elem,'s','').partition('#')[0].partition('.')[2].lower()
	foundMime = True
	if (len(ext) > 0):
		
		# typical DTB file extensions (http:#www.niso.org/workrooms/daisy/Z39-86-2005.html#Manifest)
		if (ext == 'mp3'):			setAttribute(elem,'t','audio/mpeg')						# MPEG-1/2 Layer III (MP3) audio
		elif (ext == 'smil'):		setAttribute(elem,'t','application/smil')				# SMIL files
		elif (ext == 'xml'):		setAttribute(elem,'t','application/x-dtbook+xml')		# Textual content files (dtbook)
		elif (ext == 'wav'):		setAttribute(elem,'t','audio/x-wav')						# Linear PCM - RIFF WAVE format audio
		elif (ext == 'opf'):		setAttribute(elem,'t','text/xml')						# Package file
		elif (ext == 'ncx'):		setAttribute(elem,'t','application/x-dtbncx+xml')		# Navigation Control File (NCX)
		elif (ext == 'mp4'):		setAttribute(elem,'t','audio/mpeg4-generic')				# MPEG-4 AAC audio
		elif (ext == 'jpg'):		setAttribute(elem,'t','image/jpeg')						# JPEG image
		elif (ext == 'jpe'):		setAttribute(elem,'t','image/jpeg')
		elif (ext == 'jpeg'):		setAttribute(elem,'t','image/jpeg')
		elif (ext == 'png'):		setAttribute(elem,'t','image/png')						# PNG image
		elif (ext == 'svg'):		setAttribute(elem,'t','image/svg+xml')					# Scalable Vector Graphics (SVG) image
		elif (ext == 'css'):		setAttribute(elem,'t','text/css')						# Cascading Style Sheets (CSS)
		elif (ext == 'dtd'):		setAttribute(elem,'t','application/xml-dtd')				# DTD and DTD fragments (entities or modules)
		elif (ext == 'res'):		setAttribute(elem,'t','application/x-dtbresource+xml')	# Resource file
		
		# other file extensions
		elif (ext == 'ogg'):		setAttribute(elem,'t','audio/ogg')
		elif (ext == 'au'):			setAttribute(elem,'t','audio/basic')
		elif (ext == 'snd'):		setAttribute(elem,'t','audio/basic')
		elif (ext == 'mid'):		setAttribute(elem,'t','audio/mid')
		elif (ext == 'rmi'):		setAttribute(elem,'t','audio/mid')
		elif (ext == 'aif'):		setAttribute(elem,'t','audio/x-aiff')
		elif (ext == 'aifc'):		setAttribute(elem,'t','audio/x-aiff')
		elif (ext == 'aiff'):		setAttribute(elem,'t','audio/x-aiff')
		elif (ext == 'm3u'):		setAttribute(elem,'t','audio/x-mpegurl')
		elif (ext == 'ra'):			setAttribute(elem,'t','audio/x-pn-realaudio')
		elif (ext == 'ram'):		setAttribute(elem,'t','audio/x-pn-realaudio')
		elif (ext == 'bmp'):		setAttribute(elem,'t','image/bmp')
		elif (ext == 'cod'):		setAttribute(elem,'t','image/cis-cod')
		elif (ext == 'gif'):		setAttribute(elem,'t','image/gif')
		elif (ext == 'ief'):		setAttribute(elem,'t','image/ief')
		elif (ext == 'jfif'):		setAttribute(elem,'t','image/pipeg')
		elif (ext == 'tif'):		setAttribute(elem,'t','image/tiff')
		elif (ext == 'tiff'):		setAttribute(elem,'t','image/tiff')
		elif (ext == 'ras'):		setAttribute(elem,'t','image/x-cmu-raster')
		elif (ext == 'cmx'):		setAttribute(elem,'t','image/x-cmx')
		elif (ext == 'ico'):		setAttribute(elem,'t','image/x-icon')
		elif (ext == 'pnm'):		setAttribute(elem,'t','image/x-portable-anymap')
		elif (ext == 'pbm'):		setAttribute(elem,'t','image/x-portable-bitmap')
		elif (ext == 'pgm'):		setAttribute(elem,'t','image/x-portable-graymap')
		elif (ext == 'ppm'):		setAttribute(elem,'t','image/x-portable-pixmap')
		elif (ext == 'rgb'):		setAttribute(elem,'t','image/x-rgb')
		elif (ext == 'xbm'):		setAttribute(elem,'t','image/x-xbitmap')
		elif (ext == 'xpm'):		setAttribute(elem,'t','image/x-xpixmap')
		elif (ext == 'xwd'):		setAttribute(elem,'t','image/x-xwindowdump')
		elif (ext == 'mp2'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mpa'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mpe'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mpeg'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mpg'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mpv2'):		setAttribute(elem,'t','video/mpeg')
		elif (ext == 'mov'):		setAttribute(elem,'t','video/quicktime')
		elif (ext == 'qt'):			setAttribute(elem,'t','video/quicktime')
		elif (ext == 'lsf'):		setAttribute(elem,'t','video/x-la-asf')
		elif (ext == 'lsx'):		setAttribute(elem,'t','video/x-la-asf')
		elif (ext == 'asf'):		setAttribute(elem,'t','video/x-ms-asf')
		elif (ext == 'asr'):		setAttribute(elem,'t','video/x-ms-asf')
		elif (ext == 'asx'):		setAttribute(elem,'t','video/x-ms-asf')
		elif (ext == 'avi'):		setAttribute(elem,'t','video/x-msvideo')
		elif (ext == 'movie'):		setAttribute(elem,'t','video/x-sgi-movie')
		elif (ext == '323'):		setAttribute(elem,'t','text/h323')
		elif (ext == 'htm'):		setAttribute(elem,'t','text/html')
		elif (ext == 'html'):		setAttribute(elem,'t','text/html')
		elif (ext == 'stm'):		setAttribute(elem,'t','text/html')
		elif (ext == 'uls'):		setAttribute(elem,'t','text/iuls')
		elif (ext == 'bas'):		setAttribute(elem,'t','text/plain')
		elif (ext == 'c'):			setAttribute(elem,'t','text/plain')
		elif (ext == 'h'):			setAttribute(elem,'t','text/plain')
		elif (ext == 'txt'):		setAttribute(elem,'t','text/plain')
		elif (ext == 'rtx'):		setAttribute(elem,'t','text/richtext')
		elif (ext == 'sct'):		setAttribute(elem,'t','text/scriptlet')
		elif (ext == 'tsv'):		setAttribute(elem,'t','text/tab-separated-values')
		elif (ext == 'htt'):		setAttribute(elem,'t','text/webviewhtml')
		elif (ext == 'htc'):		setAttribute(elem,'t','text/x-component')
		elif (ext == 'etx'):		setAttribute(elem,'t','text/x-setext')
		elif (ext == 'vcf'):		setAttribute(elem,'t','text/x-vcard')
		elif (ext == 'mht'):		setAttribute(elem,'t','message/rfc822')
		elif (ext == 'mhtml'):		setAttribute(elem,'t','message/rfc822')
		elif (ext == 'nws'):		setAttribute(elem,'t','message/rfc822')
		elif (ext == 'smil'):		setAttribute(elem,'t','application/smil')
		elif (ext == 'evy'):		setAttribute(elem,'t','application/envoy')
		elif (ext == 'fif'):		setAttribute(elem,'t','application/fractals')
		elif (ext == 'spl'):		setAttribute(elem,'t','application/futuresplash')
		elif (ext == 'hta'):		setAttribute(elem,'t','application/hta')
		elif (ext == 'acx'):		setAttribute(elem,'t','application/internet-property-stream')
		elif (ext == 'hqx'):		setAttribute(elem,'t','application/mac-binhex40')
		elif (ext == 'doc'):		setAttribute(elem,'t','application/msword')
		elif (ext == 'dot'):		setAttribute(elem,'t','application/msword')
		elif (ext == '*'):			setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'bin'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'class'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'dms'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'exe'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'lha'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'lzh'):		setAttribute(elem,'t','application/octet-stream')
		elif (ext == 'oda'):		setAttribute(elem,'t','application/oda')
		elif (ext == 'axs'):		setAttribute(elem,'t','application/olescript')
		elif (ext == 'pdf'):		setAttribute(elem,'t','application/pdf')
		elif (ext == 'prf'):		setAttribute(elem,'t','application/pics-rules')
		elif (ext == 'p10'):		setAttribute(elem,'t','application/pkcs10')
		elif (ext == 'crl'):		setAttribute(elem,'t','application/pkix-crl')
		elif (ext == 'ai'):			setAttribute(elem,'t','application/postscript')
		elif (ext == 'eps'):		setAttribute(elem,'t','application/postscript')
		elif (ext == 'ps'):			setAttribute(elem,'t','application/postscript')
		elif (ext == 'rtf'):		setAttribute(elem,'t','application/rtf')
		elif (ext == 'setpay'):		setAttribute(elem,'t','application/set-payment-initiation')
		elif (ext == 'setreg'):		setAttribute(elem,'t','application/set-registration-initiation')
		elif (ext == 'xla'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'xlc'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'xlm'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'xls'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'xlt'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'xlw'):		setAttribute(elem,'t','application/vnd.ms-excel')
		elif (ext == 'msg'):		setAttribute(elem,'t','application/vnd.ms-outlook')
		elif (ext == 'sst'):		setAttribute(elem,'t','application/vnd.ms-pkicertstore')
		elif (ext == 'cat'):		setAttribute(elem,'t','application/vnd.ms-pkiseccat')
		elif (ext == 'stl'):		setAttribute(elem,'t','application/vnd.ms-pkistl')
		elif (ext == 'pot'):		setAttribute(elem,'t','application/vnd.ms-powerpoint')
		elif (ext == 'pps'):		setAttribute(elem,'t','application/vnd.ms-powerpoint')
		elif (ext == 'ppt'):		setAttribute(elem,'t','application/vnd.ms-powerpoint')
		elif (ext == 'mpp'):		setAttribute(elem,'t','application/vnd.ms-project')
		elif (ext == 'wcm'):		setAttribute(elem,'t','application/vnd.ms-works')
		elif (ext == 'wdb'):		setAttribute(elem,'t','application/vnd.ms-works')
		elif (ext == 'wks'):		setAttribute(elem,'t','application/vnd.ms-works')
		elif (ext == 'wps'):		setAttribute(elem,'t','application/vnd.ms-works')
		elif (ext == 'hlp'):		setAttribute(elem,'t','application/winhlp')
		elif (ext == 'bcpio'):		setAttribute(elem,'t','application/x-bcpio')
		elif (ext == 'cdf'):		setAttribute(elem,'t','application/x-cdf')
		elif (ext == 'z'):			setAttribute(elem,'t','application/x-compress')
		elif (ext == 'tgz'):		setAttribute(elem,'t','application/x-compressed')
		elif (ext == 'cpio'):		setAttribute(elem,'t','application/x-cpio')
		elif (ext == 'csh'):		setAttribute(elem,'t','application/x-csh')
		elif (ext == 'dcr'):		setAttribute(elem,'t','application/x-director')
		elif (ext == 'dir'):		setAttribute(elem,'t','application/x-director')
		elif (ext == 'dxr'):		setAttribute(elem,'t','application/x-director')
		elif (ext == 'dvi'):		setAttribute(elem,'t','application/x-dvi')
		elif (ext == 'gtar'):		setAttribute(elem,'t','application/x-gtar')
		elif (ext == 'gz'):			setAttribute(elem,'t','application/x-gzip')
		elif (ext == 'hdf'):		setAttribute(elem,'t','application/x-hdf')
		elif (ext == 'ins'):		setAttribute(elem,'t','application/x-internet-signup')
		elif (ext == 'isp'):		setAttribute(elem,'t','application/x-internet-signup')
		elif (ext == 'iii'):		setAttribute(elem,'t','application/x-iphone')
		elif (ext == 'js'):			setAttribute(elem,'t','application/x-javascript')
		elif (ext == 'latex'):		setAttribute(elem,'t','application/x-latex')
		elif (ext == 'mdb'):		setAttribute(elem,'t','application/x-msaccess')
		elif (ext == 'crd'):		setAttribute(elem,'t','application/x-mscardfile')
		elif (ext == 'clp'):		setAttribute(elem,'t','application/x-msclip')
		elif (ext == 'dll'):		setAttribute(elem,'t','application/x-msdownload')
		elif (ext == 'm13'):		setAttribute(elem,'t','application/x-msmediaview')
		elif (ext == 'm14'):		setAttribute(elem,'t','application/x-msmediaview')
		elif (ext == 'mvb'):		setAttribute(elem,'t','application/x-msmediaview')
		elif (ext == 'wmf'):		setAttribute(elem,'t','application/x-msmetafile')
		elif (ext == 'mny'):		setAttribute(elem,'t','application/x-msmoney')
		elif (ext == 'pub'):		setAttribute(elem,'t','application/x-mspublisher')
		elif (ext == 'scd'):		setAttribute(elem,'t','application/x-msschedule')
		elif (ext == 'trm'):		setAttribute(elem,'t','application/x-msterminal')
		elif (ext == 'wri'):		setAttribute(elem,'t','application/x-mswrite')
		elif (ext == 'cdf'):		setAttribute(elem,'t','application/x-netcdf')
		elif (ext == 'nc'):			setAttribute(elem,'t','application/x-netcdf')
		elif (ext == 'pma'):		setAttribute(elem,'t','application/x-perfmon')
		elif (ext == 'pmc'):		setAttribute(elem,'t','application/x-perfmon')
		elif (ext == 'pml'):		setAttribute(elem,'t','application/x-perfmon')
		elif (ext == 'pmr'):		setAttribute(elem,'t','application/x-perfmon')
		elif (ext == 'pmw'):		setAttribute(elem,'t','application/x-perfmon')
		elif (ext == 'p12'):		setAttribute(elem,'t','application/x-pkcs12')
		elif (ext == 'pfx'):		setAttribute(elem,'t','application/x-pkcs12')
		elif (ext == 'p7b'):		setAttribute(elem,'t','application/x-pkcs7-certificates')
		elif (ext == 'spc'):		setAttribute(elem,'t','application/x-pkcs7-certificates')
		elif (ext == 'p7r'):		setAttribute(elem,'t','application/x-pkcs7-certreqresp')
		elif (ext == 'p7c'):		setAttribute(elem,'t','application/x-pkcs7-mime')
		elif (ext == 'p7m'):		setAttribute(elem,'t','application/x-pkcs7-mime')
		elif (ext == 'p7s'):		setAttribute(elem,'t','application/x-pkcs7-signature')
		elif (ext == 'sh'):			setAttribute(elem,'t','application/x-sh')
		elif (ext == 'shar'):		setAttribute(elem,'t','application/x-shar')
		elif (ext == 'swf'):		setAttribute(elem,'t','application/x-shockwave-flash')
		elif (ext == 'sit'):		setAttribute(elem,'t','application/x-stuffit')
		elif (ext == 'sv4cpio'):	setAttribute(elem,'t','application/x-sv4cpio')
		elif (ext == 'sv4crc'):		setAttribute(elem,'t','application/x-sv4crc')
		elif (ext == 'tar'):		setAttribute(elem,'t','application/x-tar')
		elif (ext == 'tcl'):		setAttribute(elem,'t','application/x-tcl')
		elif (ext == 'tex'):		setAttribute(elem,'t','application/x-tex')
		elif (ext == 'texi'):		setAttribute(elem,'t','application/x-texinfo')
		elif (ext == 'texinfo'):	setAttribute(elem,'t','application/x-texinfo')
		elif (ext == 'roff'):		setAttribute(elem,'t','application/x-troff')
		elif (ext == 't'):			setAttribute(elem,'t','application/x-troff')
		elif (ext == 'tr'):			setAttribute(elem,'t','application/x-troff')
		elif (ext == 'man'):		setAttribute(elem,'t','application/x-troff-man')
		elif (ext == 'me'):			setAttribute(elem,'t','application/x-troff-me')
		elif (ext == 'ms'):			setAttribute(elem,'t','application/x-troff-ms')
		elif (ext == 'ustar'):		setAttribute(elem,'t','application/x-ustar')
		elif (ext == 'src'):		setAttribute(elem,'t','application/x-wais-source')
		elif (ext == 'cer'):		setAttribute(elem,'t','application/x-x509-ca-cert')
		elif (ext == 'crt'):		setAttribute(elem,'t','application/x-x509-ca-cert')
		elif (ext == 'der'):		setAttribute(elem,'t','application/x-x509-ca-cert')
		elif (ext == 'pko'):		setAttribute(elem,'t','application/ynd.ms-pkipko')
		elif (ext == 'zip'):		setAttribute(elem,'t','application/zip')
		elif (ext == 'flr'):		setAttribute(elem,'t','x-world/x-vrml')
		elif (ext == 'vrml'):		setAttribute(elem,'t','x-world/x-vrml')
		elif (ext == 'wrl'):		setAttribute(elem,'t','x-world/x-vrml')
		elif (ext == 'wrz'):		setAttribute(elem,'t','x-world/x-vrml')
		elif (ext == 'xaf'):		setAttribute(elem,'t','x-world/x-vrml')
		elif (ext == 'xof'):		setAttribute(elem,'t','x-world/x-vrml')
		else: foundMime = False
		if (foundMime):
			return
	
	# determine type by node name
	if (elem[0] == 'audio'):		setAttribute(elem,'t','audio/basic')
	elif (elem[0] == 'text'):		setAttribute(elem,'t','text/plain')
	elif (elem[0] == 'textstream'):	setAttribute(elem,'t','text/plain')
	elif (elem[0] == 'animation'):	setAttribute(elem,'t','video/')
	elif (elem[0] == 'video'):		setAttribute(elem,'t','video/')
	elif (elem[0] == 'img'):		setAttribute(elem,'t','image/')
	elif (elem[0] == 'ref'):		setAttribute(elem,'t','multipart/mixed')
	
	# Unable to determine (or even guess) MIME type! Set type to binary file?
	#else: setAttribute(elem,'t','application/octet-stream')

nccFile = open(tempDir+"/ncc.json")
ncc = json.load(nccFile)
nccFile.close()
progress(1)

# this is what we hope to get from ncc.json
metadata = ["metadata", dict()]	# <metadata name=value name=value /> => ["metadata", { name:value , name:value}]
toc = ["toc"]					# <toc><h title= level= id= begin= end= /><h .../></toc> => ["toc", ["h",{ title,level,id,begin,end }]]
pagelist = ["pagelist"]			# <pagelist><p page= id= begin= end= /><p .../></pagelist> => ["pagelist",["p",{ page,id,begin,end }]]
theFlow = []					# { nodeName, clazz, smil, smilFragment, text } (for use in this script only; not JsonML)

head = None
body = None
if (isinstance(ncc[1], dict)):
	head = ncc[2]
	body = ncc[3]
else:
	head = ncc[1]
	body = ncc[2]

# get metadata
totalTime = 1
for meta in head:
	if (not isinstance(meta, list)):
		continue
	if (hasAttribute(meta, 'name') and hasAttribute(meta, 'content')):
		if (getAttribute(meta, 'name', 'undefined') == 'ncc:totalTime' or getAttribute(meta, 'name', 'undefined') == 'ncc:totaltime'):
			fraction = getAttribute(meta, 'content', str(totalTime)).split('.')
			hms = fraction[0].split(':')
			if (len(hms)==3):
				totalTime = (float(hms[0])*24*60) + (float(hms[1])*60) + (float(hms[2])*1)
			elif (len(hms)==2):
				totalTime = (float(hms[0])*60) + (float(hms[1])*1)
			else:
				totalTime = float(hms[0])
			if (len(fraction)>1):
				fraction = fraction[1]
			else:
				fraction = '0'
			totalTime = float(str(totalTime).split('.')[0]+'.'+fraction)
		setAttribute(metadata,getAttribute(meta, 'name', 'undefined'),getAttribute(meta, 'content', 'undefined'))
progress(2)

# get toc, pagelist and theFlow
for child in body:
	if (not isinstance(child, list)):
		continue
	a = None
	for grandchild in child:
		if (isinstance(grandchild, list) and grandchild[0] == 'a'):
			a = grandchild
			break
	if (a == None):
		continue
	
	clazz = getAttribute(child, 'class', '')
	href = getAttribute(a, 'href', '')
	smil = href.partition('#')
	
	alreadyThere = -1
	for j in range(len(theFlow)):
		if (theFlow[j]['smil'] == smil[0]):
			alreadyThere = j
			break
	
	id = ""
	if (alreadyThere >= 0):
		id = 'smil'+unicode(alreadyThere)+'_'+smil[2]
	else:
		id = 'smil'+unicode(len(theFlow))+'_'+smil[2]
		#id = 'smil'+int(len(theFlow))+'_'+smil[2]
	
	text = textContent(a)
	
	if (child[0] == 'h1'):
		toc.append(["h",dict({'title': text, 'level':1 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'h2'):
		toc.append(["h",dict({'title': text, 'level':2 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'h3'):
		toc.append(["h",dict({'title': text, 'level':3 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'h4'):
		toc.append(["h",dict({'title': text, 'level':4 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'h5'):
		toc.append(["h",dict({'title': text, 'level':5 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'h6'):
		toc.append(["h",dict({'title': text, 'level':6 , 'i': id, 'b':-1 , 'e':-1})])
	if (child[0] == 'span'):
		if (clazz.startswith("page-")):
			m = re.match(".*?(\d+).*?", textContent(a)) # TODO: handle roman numerals
			if m is not None:
				pagelist.append(["p",dict({'page':int(m.group(1)) , 'i':id , 'b':-1 , 'e':-1})])
			
	#if (child[0] == 'div'):
	
	if (alreadyThere < 0):
		theFlow.append(dict({'nodeName': child[0], 'clazz': clazz, 'smil': smil[0], 'smilFragment': smil[2], 'text': text}))
progress(4)

smil = ["s" , {'b':0}] # <s b= e= B= E= d= >...</s>
for currentFlow in range(len(theFlow)):
	smilFile = open(tempDir+"/"+theFlow[currentFlow]['smil'].rpartition('/')[2]+".json")
	smil.append(json.load(smilFile))
	smilFile.close()
progress(6)

fixTimesStart = 6
fixTimesEnd = 90
fixTimesIteration = 0
fixTimesCurrentIteration = 0.0

if (sys.getrecursionlimit() < 100):
	sys.setrecursionlimit(100)
def fixTimes(parent, current, siblingNr, depth):
	global debug
	
	dbg_indent = ""
	for i in range(0,depth):
		dbg_indent = dbg_indent+'  '
	
	begin = float(getAttribute(current, 'b', -1))
	end = float(getAttribute(current, 'e', -1))
	clipBegin = float(getAttribute(current, 'B', -1))
	clipEnd = float(getAttribute(current, 'E', -1))
	dur = float(getAttribute(current, 'd', -1))
	type = unicode(getAttribute(current, 't', ''))
	
	currentUpdated = False
	determinedSomething = True
	while (determinedSomething):
		determinedSomething = False
		
		# 1a. First try fixing the element by itself
		if (dur < 0):
			if (begin >= 0 and end >= 0):
				if (end - begin >= 0):
					dur = end - begin
					determinedSomething = True
					if (debug): print(dbg_indent+'1a1. dur='+unicode(dur))
			elif (clipBegin >= 0 and clipEnd >= 0):
				if (clipEnd - clipBegin >= 0):
					dur = clipEnd - clipBegin
					determinedSomething = True
					if (debug): print(dbg_indent+'1a2. dur='+unicode(dur))
		
		# 1b
		if (begin < 0 and dur >= 0 and end >= 0):
			if (end - dur >= 0):
				begin = end - dur
				determinedSomething = True
				if (debug): print(dbg_indent+'1b. begin='+unicode(begin))
		
		# 1c
		if (end < 0 and dur >= 0 and begin >= 0):
			if (begin + dur >= 0):
				end = begin + dur
				determinedSomething = True
				if (debug): print(dbg_indent+'1c. end='+unicode(end))
		
		# 1d
		if (clipBegin < 0 and dur >= 0 and clipEnd >= 0):
			if (clipEnd - dur >= 0):
				clipBegin = clipEnd - dur
				determinedSomething = True
				if (debug): print(dbg_indent+'1d. clipBegin='+unicode(clipBegin))
		
		# 1e
		if (clipEnd < 0 and dur >= 0 and clipBegin >= 0):
			if (clipBegin + dur >= 0):
				clipEnd = clipBegin + dur
				determinedSomething = True
				if (debug): print(dbg_indent+'1e. clipEnd='+unicode(clipEnd))
		
		
		if (not determinedSomething):
			# Second, use the elements children, siblings and parent to infer missing values.
			
			# __'b'__
			# 2a. if parent is par or is first sibling try using the parent
			if (begin < 0 and parent != None and (siblingNr == 0 or parent[0] == 'p')):
				if (getAttribute(parent,'b',-1) >= 0):
					begin = getAttribute(parent,'b',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'2a. begin='+unicode(begin))
			
			# 2b. if parent is seq and is not first sibling try using previous sibling
			if (begin < 0 and siblingNr > 0 and parent != None and parent[0] == 's'):
				if (getAttribute(getChild(parent,siblingNr-1),'e',-1) >= 0):
					begin = getAttribute(getChild(parent,siblingNr-1),'e',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'2b. begin='+unicode(begin))
			
			# 2c. If parent is par try using any sibling
			if (parent != None and parent[0] == 'p'):
				for s in range(0,numberOfChildren(parent)):
					sib = getChild(parent,s)
					if (begin < 0 and getAttribute(getChild(parent,s),'b',-1) >= 0):
						begin = getAttribute(getChild(parent,s),'b',-1)
						determinedSomething = True
						if (debug): print(dbg_indent+'2c1. begin='+unicode(begin))
					if (end < 0 and getAttribute(getChild(parent,s),'e',-1) >= 0):
						end = getAttribute(getChild(parent,s),'e',-1)
						determinedSomething = True
						if (debug): print(dbg_indent+'2c2. end='+unicode(end))
			
			# 2d. if has children, try using any of the children pars
			if (current[0] == 'p'):
				for c in range(0,numberOfChildren(current)):
					if (begin < 0 and getAttribute(getChild(current,c),'b',-1) >= 0):
						begin = getAttribute(getChild(current,c),'b',-1)
						determinedSomething = True
						if (debug): print(dbg_indent+'2d. begin='+unicode(begin))
						break
					if (end < 0 and getAttribute(getChild(current,c),'e',-1) >= 0):
						end = getAttribute(getChild(current,c),'e',-1)
						determinedSomething = True
						if (debug): print(dbg_indent+'2d. end='+unicode(end))
						break
			
			# 2e. if has children, try using the first children seq
			if (begin < 0 and current[0] == 's'):
				if (numberOfChildren(current) > 0 and getAttribute(getChild(current,0),'b',-1) >= 0):
					begin = getAttribute(getChild(current,0),'b',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'2e. begin='+unicode(begin))
			
			# __'e'__
			# 3a. if is par and all children of type a/v/par/seq has an end, use max(getChild(current,'e')).
			if (end < 0 and current[0] == 'p'):
				newEnd = -1
				for c in range(0,numberOfChildren(current)):
					child = getChild(current,c)
					parted = getAttribute(child,'t','').partition('/')
					if (not (child[0] == 'p' or child[0] == 's' or parted[0] == 'a' or parted[0] == 'v')):
						continue
					if (getAttribute(child,'e',-1) >= 0):
						newEnd = max(newEnd,getAttribute(child,'e',-1))
					else:
						newEnd = -1
						break
				if (newEnd >= 0):
					end = newEnd
					determinedSomething = True
					if (debug): print(dbg_indent+'3a. end='+unicode(end))
			
			# 3b. if is seq, try using getAttribute(getChild(current,last),'e',-1)
			if (end < 0 and current[0] == 's'):
				if (numberOfChildren(current) > 0 and getAttribute(getChild(current,numberOfChildren(current)-1),'e',-1) >= 0):
					end = getAttribute(getChild(current,numberOfChildren(current)-1),'e',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'3b. end='+unicode(end))
			
			# 3c. if parent is seq and is last child of parent, try using parent
			if (end < 0 and parent != None and parent[0] == 's' and siblingNr == numberOfChildren(parent)-1):
				if (getAttribute(parent,'e',-1) >= 0):
					end = getAttribute(parent,'e',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'3c. end='+unicode(end))
			
			# 3d. if parent is seq and is not last child of parent, try using next sibling
			if (end < 0 and parent != None and parent[0] == 's' and siblingNr < numberOfChildren(parent)-1):
				if (getAttribute(getChild(parent,siblingNr+1),'b',-1) >= 0):
					end = getAttribute(getChild(parent,siblingNr+1),'b',-1)
					determinedSomething = True
					if (debug): print(dbg_indent+'3d. end='+unicode(end))
			
			# 3e. if parent is par and all siblings of type a/v/par/seq has an end use max(getChild(parent,'e'))
			if (end < 0 and parent != None and parent[0] == 'p'):
				newEnd = -1
				for c in range(0,numberOfChildren(parent)):
					sibling = getChild(parent,c)
					parted = getAttribute(sibling,'t','').partition('/')
					if (not (sibling[0] == 's' or sibling[0] == 'p' or parted[0] == 'a' or parted[0] == 'v')):
						continue
					if (getAttribute(sibling,'e',-1) >= 0):
						newEnd = max(newEnd,getAttribute(sibling,'e',-1))
					else:
						newEnd = -1
						break
				if (newEnd >= 0):
					end = newEnd
					determinedSomething = True
					if (debug): print(dbg_indent+'3e. end='+unicode(end))
			
			# __'B'__
			# 4a. if parent is seq and is not first sibling, try using previous sibling with same src
			if (clipBegin < 0 and siblingNr > 0 and parent != None and parent[0] == 's'):
				for s in range(siblingNr-1,-1,-1):
					if (getAttribute(current,'s','').partition('#')[0] == getAttribute(getChild(parent,s),'s','').partition('#')[0]):
						if (getAttribute(getChild(parent,s),'E',-1) >= 0):
							clipBegin = getAttribute(getChild(parent,s),'E',-1)
							determinedSomething = True
							if (debug): print(dbg_indent+'4a. clipBegin='+unicode(clipBegin))
						break
			
			# __'E'__
			# 5a. if parent is seq and is not last sibling, try using next sibling with same src
			if (clipEnd < 0 and parent != None and siblingNr < numberOfChildren(parent)-1 and parent[0] == 's'):
				for s in range(siblingNr+1,numberOfChildren(parent)):
					if (getAttribute(current,'s','').partition('#')[0] == getAttribute(getChild(parent,s),'s','').partition('#')[0]):
						if (getAttribute(getChild(parent,s),'B',-1) >= 0):
							clipEnd = getAttribute(getChild(parent,s),'B',-1)
							determinedSomething = True
							if (debug): print(dbg_indent+'5a. clipEnd='+unicode(clipEnd))
						break
		
		# Update progress
		global totalTime
		global fixTimesStart
		global fixTimesEnd
		global fixTimesIteration
		global fixTimesCurrentIteration
		if (end > totalTime):
			totalTime = end
		if (begin > totalTime):
			totalTime = begin
		if (begin/totalTime - fixTimesCurrentIteration/totalTime > 0.25):
			fixTimesCurrentIteration = begin
			progress(str(100*((1-0.3**(fixTimesIteration+fixTimesCurrentIteration/totalTime))*(fixTimesEnd-fixTimesStart)/100. + fixTimesStart/100.)))
		
		# Finally; recursively process elements (could be optimized further, but this should do for now)
		if (not determinedSomething):
			for child in range(0,numberOfChildren(current)):
				if (fixTimes(current, getChild(current,child), child, depth+1)):
					determinedSomething = True
					if (debug): print(dbg_indent+'recursed '+unicode(getChild(current,child)[0]))
		
		if (determinedSomething):
			currentUpdated = True
	
	setAttribute(current,'b',begin)
	setAttribute(current,'e',end)
	setAttribute(current,'B',clipBegin)
	setAttribute(current,'E',clipEnd)
	setAttribute(current,'d',dur)
	return currentUpdated

#while (fixTimes(None, smil, 0) or getAttribute(smil[0],'b',-1) == -1):
while (fixTimes(None, smil, 0, 0)):
	fixTimesIteration = fixTimesIteration + 1
	fixTimesCurrentIteration = 0
	if (debug): print "iteration ", fixTimesIteration
	continue
progress(fixTimesEnd)

def prependId(current, text):
	if (hasAttribute(current,'i')):
		setAttribute(current,'i',text+getAttribute(current,'i',''))
	for c in range(0,numberOfChildren(current)):
		prependId(getChild(current,c), text)
for i in range(0,numberOfChildren(smil)):
	prependId(getChild(smil,i),'smil'+unicode(i)+'_')


def postProcess(current):
	# check pagelist
	for i in range(1,len(pagelist)):
		if (getAttribute(pagelist[i],'i','0') == getAttribute(current,'i','1')):
			setAttribute(pagelist[i],'b',unicode(round(float(getAttribute(current,'b',"-1")),4)))
			setAttribute(pagelist[i],'e',unicode(round(float(getAttribute(current,'e',"-1")),4)))
			break
	
	# check toc
	for i in range(1,len(toc)):
		if (getAttribute(toc[i],'i','0') == getAttribute(current,'i','1')):
			setAttribute(toc[i],'b',unicode(round(float(getAttribute(current,'b',"-1")),4)))
			setAttribute(toc[i],'e',unicode(round(float(getAttribute(current,'e',"-1")),4)))
			break
	
	# round times and convert to strings
	if (current[0] != 's' and current[0] != 'p'):
		determineMimeType(current)
	
	if (hasAttribute(current,'b') and getAttribute(current,'b',-1) != -1):
		setAttribute(current,'b',unicode(round(float(getAttribute(current,'b',-1)),4)))
	#else: removeAttribute(current,'b')
	
	if (hasAttribute(current,'e') and getAttribute(current,'e',-1) != -1):
		setAttribute(current,'e',unicode(round(float(getAttribute(current,'e',-1)),4)))
	#else: removeAttribute(current,'e')
	
	if (hasAttribute(current,'B') and getAttribute(current,'B',-1) != -1):
		setAttribute(current,'B',unicode(round(float(getAttribute(current,'B',-1)),4)))
	#else: removeAttribute(current,'B')
	
	if (hasAttribute(current,'E') and getAttribute(current,'E',-1) != -1):
		setAttribute(current,'E',unicode(round(float(getAttribute(current,'E',-1)),4)))
	#else: removeAttribute(current,'E')
	
	if (hasAttribute(current,'d') and getAttribute(current,'d',-1) != -1):
		setAttribute(current,'d',unicode(round(float(getAttribute(current,'d',-1)),4)))
	#else: removeAttribute(current,'d')
	
	# recurse
	if (len(current) > 1):
		for child in range(2,len(current)):
			postProcess(current[child])

postProcess(smil)
progress(99)

smilFile = open(tempDir+"/smil.json", 'w')
json.dump(smil, smilFile, indent=0, separators=(',',':')) # indent=0 so that linebreaks are added. JavaScript likes linebreaks.
smilFile.close()

tocFile = open(tempDir+"/toc.json", 'w')
json.dump(toc, tocFile, indent=0)
tocFile.close()

pagelistFile = open(tempDir+"/pagelist.json", 'w')
json.dump(pagelist, pagelistFile, indent=0)
pagelistFile.close()

metadataFile = open(tempDir+"/metadata.json", 'w')
json.dump(metadata, metadataFile, indent=0)
metadataFile.close()

progress(100)