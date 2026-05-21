import { useRef, memo } from 'react';

const NftCard = memo(function NftCard({ nft, onHoverStart, onHoverEnd }) {
    const cardRef = useRef(null);
    const href = nft?.nft_url || nft?.collection_url || '#';
    const isLink = Boolean(nft?.nft_url || nft?.collection_url);
    const displayImage = nft?.thumbnail_url || nft?.image_url || '';
    const imageSrc = displayImage.startsWith('http')
        ? displayImage
        : `/${displayImage.replace(/^\//, '')}`;

    const handleEnter = () => onHoverStart?.(nft, cardRef.current);
    const handleLeave = () => onHoverEnd?.(cardRef.current);

    return (
        <a
            ref={cardRef}
            className="nft-board__card"
            href={href}
            style={{ '--card-bg-image': `url(${imageSrc})` }}
            aria-label={isLink ? `View ${nft.name}` : nft.name}
            onMouseEnter={handleEnter}
            onMouseLeave={handleLeave}
            onFocus={handleEnter}
            onBlur={handleLeave}
            onClick={(e) => { if (!isLink) e.preventDefault(); }}
            onKeyDown={(e) => { if (e.key === ' ') { e.preventDefault(); cardRef.current?.click(); } }}
        >
            <img
                className="nft-board__card-image"
                src={imageSrc}
                alt={nft.name}
                loading="lazy"
                draggable={false}
            />
        </a>
    );
});

export default NftCard;
