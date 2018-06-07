<?php

/**
 * Library include file
 *
 * This file contains all includes to the rest of the SabreDAV library
 * Make sure the lib/ directory is in PHP's include_path
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/* Utilities */
include dirname(__FILE__) . '/HTTP/Util.php';
include dirname(__FILE__) . '/HTTP/Response.php';
include dirname(__FILE__) . '/HTTP/Request.php';
include dirname(__FILE__) . '/HTTP/AbstractAuth.php';
include dirname(__FILE__) . '/HTTP/BasicAuth.php';
include dirname(__FILE__) . '/HTTP/DigestAuth.php';
include dirname(__FILE__) . '/HTTP/AWSAuth.php';

/* Version */
include dirname(__FILE__) . '/DAV/Version.php';
include dirname(__FILE__) . '/HTTP/Version.php';

/* Exceptions */
include dirname(__FILE__) . '/DAV/Exception.php';
include dirname(__FILE__) . '/DAV/Exception/BadRequest.php';
include dirname(__FILE__) . '/DAV/Exception/Conflict.php';
include dirname(__FILE__) . '/DAV/Exception/FileNotFound.php';
include dirname(__FILE__) . '/DAV/Exception/InsufficientStorage.php';
include dirname(__FILE__) . '/DAV/Exception/Locked.php';
include dirname(__FILE__) . '/DAV/Exception/LockTokenMatchesRequestUri.php';
include dirname(__FILE__) . '/DAV/Exception/MethodNotAllowed.php';
include dirname(__FILE__) . '/DAV/Exception/NotImplemented.php';
include dirname(__FILE__) . '/DAV/Exception/Forbidden.php';
include dirname(__FILE__) . '/DAV/Exception/PreconditionFailed.php';
include dirname(__FILE__) . '/DAV/Exception/RequestedRangeNotSatisfiable.php';
include dirname(__FILE__) . '/DAV/Exception/UnsupportedMediaType.php';
include dirname(__FILE__) . '/DAV/Exception/NotAuthenticated.php';

include dirname(__FILE__) . '/DAV/Exception/ConflictingLock.php';
include dirname(__FILE__) . '/DAV/Exception/ReportNotImplemented.php';
include dirname(__FILE__) . '/DAV/Exception/InvalidResourceType.php';

/* Properties */
include dirname(__FILE__) . '/DAV/Property.php';
include dirname(__FILE__) . '/DAV/Property/GetLastModified.php';
include dirname(__FILE__) . '/DAV/Property/ResourceType.php';
include dirname(__FILE__) . '/DAV/Property/SupportedLock.php';
include dirname(__FILE__) . '/DAV/Property/LockDiscovery.php';
include dirname(__FILE__) . '/DAV/Property/IHref.php';
include dirname(__FILE__) . '/DAV/Property/Href.php';
include dirname(__FILE__) . '/DAV/Property/HrefList.php';
include dirname(__FILE__) . '/DAV/Property/SupportedReportSet.php';
include dirname(__FILE__) . '/DAV/Property/Response.php';
include dirname(__FILE__) . '/DAV/Property/ResponseList.php';

/* Node interfaces */
include dirname(__FILE__) . '/DAV/INode.php';
include dirname(__FILE__) . '/DAV/IFile.php';
include dirname(__FILE__) . '/DAV/ICollection.php';
include dirname(__FILE__) . '/DAV/IProperties.php';
include dirname(__FILE__) . '/DAV/ILockable.php';
include dirname(__FILE__) . '/DAV/IQuota.php';
include dirname(__FILE__) . '/DAV/IExtendedCollection.php';

/* Node abstract implementations */
include dirname(__FILE__) . '/DAV/Node.php';
include dirname(__FILE__) . '/DAV/File.php';
include dirname(__FILE__) . '/DAV/Collection.php';
include dirname(__FILE__) . '/DAV/Directory.php';

/* Utilities */
include dirname(__FILE__) . '/DAV/SimpleCollection.php';
include dirname(__FILE__) . '/DAV/SimpleDirectory.php';
include dirname(__FILE__) . '/DAV/XMLUtil.php';
include dirname(__FILE__) . '/DAV/URLUtil.php';

/* Filesystem implementation */
include dirname(__FILE__) . '/DAV/FS/Node.php';
include dirname(__FILE__) . '/DAV/FS/File.php';
include dirname(__FILE__) . '/DAV/FS/Directory.php';

/* Advanced filesystem implementation */
include dirname(__FILE__) . '/DAV/FSExt/Node.php';
include dirname(__FILE__) . '/DAV/FSExt/File.php';
include dirname(__FILE__) . '/DAV/FSExt/Directory.php';

/* Trees */
include dirname(__FILE__) . '/DAV/Tree.php';
include dirname(__FILE__) . '/DAV/ObjectTree.php';
include dirname(__FILE__) . '/DAV/Tree/Filesystem.php';

/* Server */
include dirname(__FILE__) . '/DAV/Server.php';
include dirname(__FILE__) . '/DAV/ServerPlugin.php';

/* Browser */
include dirname(__FILE__) . '/DAV/Browser/Plugin.php';
include dirname(__FILE__) . '/DAV/Browser/MapGetToPropFind.php';
include dirname(__FILE__) . '/DAV/Browser/GuessContentType.php';

/* Locks */
include dirname(__FILE__) . '/DAV/Locks/LockInfo.php';
include dirname(__FILE__) . '/DAV/Locks/Plugin.php';
include dirname(__FILE__) . '/DAV/Locks/Backend/Abstract.php';
include dirname(__FILE__) . '/DAV/Locks/Backend/FS.php';
include dirname(__FILE__) . '/DAV/Locks/Backend/PDO.php';
include dirname(__FILE__) . '/DAV/Locks/Backend/File.php';

/* Temporary File Filter plugin */
include dirname(__FILE__) . '/DAV/TemporaryFileFilterPlugin.php';

/* Authentication plugin */
include dirname(__FILE__) . '/DAV/Auth/Plugin.php';
include dirname(__FILE__) . '/DAV/Auth/IBackend.php';
include dirname(__FILE__) . '/DAV/Auth/Backend/AbstractDigest.php';
include dirname(__FILE__) . '/DAV/Auth/Backend/AbstractBasic.php';
include dirname(__FILE__) . '/DAV/Auth/Backend/File.php';
include dirname(__FILE__) . '/DAV/Auth/Backend/PDO.php';

/* DavMount plugin */
include dirname(__FILE__) . '/DAV/Mount/Plugin.php';

